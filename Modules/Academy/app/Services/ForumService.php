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
     * Sujets de l'item, ÉPINGLÉS en tête puis du plus récent au plus ancien. Charge
     * en lot l'auteur, le nombre de réponses, et (pour la page courante) les réponses
     * avec leur auteur (anti N+1 à l'affichage). Le nom de page est propre à l'item
     * (« forum{id}page ») pour permettre plusieurs forums dans une même leçon.
     *
     * @return LengthAwarePaginator<int, ForumTopic>
     */
    public static function topics(LessonItem $item, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return ForumTopic::where('lesson_item_id', $item->id)
            ->with([
                'user:id,name',
                // Bornée (POSTS_PER_TOPIC) : la liste ne charge jamais un nombre non
                // borné de réponses par sujet. Ordre chronologique conservé.
                'posts' => fn ($q) => $q->orderBy('created_at')->with('user:id,name')->limit(self::POSTS_PER_TOPIC),
            ])
            ->withCount('posts')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'forum'.$item->id.'page');
    }
}
