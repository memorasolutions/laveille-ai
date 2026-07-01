<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FORUM - SOURCE UNIQUE (DRY) de l'activité « forum de discussion » (item de leçon
 * « forum », type Moodle « Forum »). Lue côté SERVEUR par le contrôleur d'actions, le
 * lecteur (lesson.blade) et l'éditeur (CourseEditor). La configuration, la liste
 * paginée des sujets (épinglés en tête) et les bornes ne vivent qu'ICI.
 *
 * Le payload de l'item porte (aucune nouvelle colonne, comme quiz/choice/feedback) :
 *   - intro                : texte d'introduction facultatif ;
 *   - allow_student_topics : les étudiants peuvent ouvrir des sujets (défaut true) ;
 *   - locked               : forum en lecture seule global (défaut false) - aucun
 *                            nouveau sujet ni réponse, sauf pour un gérant.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academy\Models\ForumTopic;
use Modules\Academy\Models\LessonItem;

final class ForumService
{
    /** Longueur maximale du titre d'un sujet (anti-abus). */
    public const TITLE_MAX = 200;

    /** Longueur maximale d'un corps (sujet ou réponse, anti-abus). */
    public const BODY_MAX = 10000;

    /** Nom du champ honeypot anti-spam (caché, doit rester vide). */
    public const HONEYPOT = 'hp_url';

    /** Nombre de sujets par page (pagination simple). */
    public const PER_PAGE = 10;

    /**
     * Borne du nombre de réponses chargées par sujet dans la VUE LISTE (anti-explosion
     * mémoire/requête : un sujet très actif ne doit pas charger des milliers de posts à
     * l'affichage de la liste). Le badge « N réponses » reste exact (withCount, compte
     * total). On charge les PREMIÈRES réponses en ordre chronologique (lecture naturelle
     * du fil depuis le sujet) ; au-delà de cette borne, un repère « voir plus » est
     * affiché. Laravel 12 applique cette limite PAR sujet (limited eager loading).
     */
    public const POSTS_PER_TOPIC = 50;

    /**
     * Borne haute d'un horodatage vidéo (secondes) - anti-abus (parseTimestamp
     * rejette tout au-delà). 24h est très largement au-dessus de toute leçon vidéo
     * réelle du catalogue ; c'est une borne de sécurité, pas une limite fonctionnelle.
     */
    public const VIDEO_TIMESTAMP_MAX = 86400;

    // ─────────────────────────────────────────────────────────────────────────────
    // DISCUSSION SOCIALE PAR VIDÉO (dette D-video-discussion, LMS 2026)
    // ─────────────────────────────────────────────────────────────────────────────
    // Un sujet/réponse de forum peut être ancré à un instant précis de la vidéo
    // (ForumTopic/ForumPost::video_timestamp_seconds, colonnes additives nullable).
    // parseTimestamp()/formatTimestamp() sont la SOURCE UNIQUE de conversion
    // texte <-> secondes, utilisées par le contrôleur (écriture) et les modèles/vues
    // (affichage), pour ne jamais dupliquer la logique de format « mm:ss ».

    /**
     * Convertit une saisie utilisateur « mm:ss » ou « h:mm:ss » en secondes. Retourne
     * null si vide ou mal formée (rejet silencieux et propre : le post reste publiable
     * sans horodatage plutôt que d'échouer toute la soumission). Borné par
     * VIDEO_TIMESTAMP_MAX (anti-abus).
     */
    public static function parseTimestamp(?string $raw): ?int
    {
        $raw = is_string($raw) ? trim($raw) : '';
        if ($raw === '' || ! preg_match('/^(?:(\d{1,2}):)?(\d{1,3}):([0-5]\d)$/', $raw, $m)) {
            return null;
        }

        $hours   = ($m[1] ?? '') !== '' ? (int) $m[1] : 0;
        $minutes = (int) $m[2];
        $seconds = (int) $m[3];
        $total   = ($hours * 3600) + ($minutes * 60) + $seconds;

        return $total <= self::VIDEO_TIMESTAMP_MAX ? $total : null;
    }

    /**
     * Formate des secondes en « m:ss » (ou « h:mm:ss » au-delà d'une heure). Null en
     * entrée => null en sortie (aucun badge affiché).
     */
    public static function formatTimestamp(?int $seconds): ?string
    {
        if ($seconds === null || $seconds < 0) {
            return null;
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // LECTURE DE LA CONFIGURATION (payload)
    // ─────────────────────────────────────────────────────────────────────────────

    public static function intro(LessonItem $item): string
    {
        $intro = is_array($item->payload ?? null) ? ($item->payload['intro'] ?? '') : '';

        return is_string($intro) ? $intro : '';
    }

    /** Les étudiants peuvent-ils ouvrir des sujets ? DÉFAUT true (clé absente = autorisé). */
    public static function allowsStudentTopics(LessonItem $item): bool
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['allow_student_topics'] ?? null) : null;

        return $raw === null ? true : (bool) $raw;
    }

    /** Forum en lecture seule global (défaut false). */
    public static function isLocked(LessonItem $item): bool
    {
        return (bool) (is_array($item->payload ?? null) ? ($item->payload['locked'] ?? false) : false);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // LISTE DES SUJETS (épinglés d'abord, puis récents) - paginée
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Sujets de l'item, ÉPINGLÉS en tête puis du plus récent au plus ancien (défaut),
     * ou dans l'ordre du contenu vidéo quand $sortByVideoTime=true (discussion sociale
     * par vidéo, dette D-video-discussion : suit le fil dans l'ordre où les moments de
     * la vidéo sont évoqués plutôt que dans l'ordre de publication). Charge en lot
     * l'auteur, le nombre de réponses, et (pour la page courante) les réponses avec
     * leur auteur (anti N+1 à l'affichage). Le nom de page est propre à l'item
     * (« forum{id}page ») pour permettre plusieurs forums dans une même leçon.
     *
     * @return LengthAwarePaginator<int, ForumTopic>
     */
    public static function topics(
        LessonItem $item,
        int $perPage = self::PER_PAGE,
        bool $sortByVideoTime = false
    ): LengthAwarePaginator {
        $query = ForumTopic::where('lesson_item_id', $item->id)
            ->with([
                'user:id,name',
                // Bornée (POSTS_PER_TOPIC) : la liste ne charge jamais un nombre non
                // borné de réponses par sujet. Ordre chronologique par défaut, ou par
                // horodatage vidéo (nulls en dernier) quand $sortByVideoTime=true.
                'posts' => fn ($q) => $sortByVideoTime
                    ? $q->orderByRaw('video_timestamp_seconds IS NULL')->orderBy('video_timestamp_seconds')->orderBy('created_at')->with('user:id,name')->limit(self::POSTS_PER_TOPIC)
                    : $q->orderBy('created_at')->with('user:id,name')->limit(self::POSTS_PER_TOPIC),
            ])
            ->withCount('posts')
            ->orderByDesc('is_pinned');

        if ($sortByVideoTime) {
            // Nulls en dernier (aucun ancrage = fin de liste), puis ordre chronologique
            // du contenu vidéo, puis date de publication en repli (égalité/absence).
            $query->orderByRaw('video_timestamp_seconds IS NULL')
                ->orderBy('video_timestamp_seconds')
                ->orderBy('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage, ['*'], 'forum'.$item->id.'page');
    }
}
