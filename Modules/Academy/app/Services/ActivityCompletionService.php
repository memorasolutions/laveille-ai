<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-c - ACHÈVEMENT D'ACTIVITÉ CONFIGURABLE (parité Moodle « activity completion »).
 * SOURCE UNIQUE (DRY) du CRITÈRE de complétion d'un item de leçon, lu côté SERVEUR.
 *
 * Le critère est porté par payload['completion'] de l'item (aucune nouvelle table,
 * comme passing_score / grading_method / review_options). Critères (liste blanche) :
 *   - manual    : l'étudiant clique « Marquer comme terminé » (comportement HISTORIQUE
 *                 des items vidéo/document) ;
 *   - view       : complété AUTOMATIQUEMENT à la consultation de la leçon (idempotent) ;
 *   - min_grade  : item quiz complété UNIQUEMENT si réussi (note ≥ passing_score) -
 *                 c'est le comportement HISTORIQUE d'un quiz, donc son DÉFAUT.
 *   - vote       : item « choice » (sondage) complété dès que l'étudiant a voté -
 *                 c'est le comportement naturel d'un sondage, donc son DÉFAUT.
 *   - submit     : item « feedback » (questionnaire) complété dès que l'étudiant a
 *                 répondu - comportement naturel d'un sondage de rétroaction, son DÉFAUT.
 *
 * RÉTROCOMPATIBILITÉ STRICTE (le cœur du design) : un item SANS payload['completion']
 * retombe sur le DÉFAUT PROPRE À SON TYPE - 'min_grade' pour un quiz, 'manual' pour le
 * reste. Ainsi, tous les items existants conservent EXACTEMENT leur comportement actuel
 * (un quiz historique se complète toujours à la réussite ; une vidéo/un document garde
 * son bouton manuel). Seul un item où un gérant a EXPLICITEMENT choisi « view » (ou un
 * autre critère non-défaut) change de comportement.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

final class ActivityCompletionService
{
    /** Critères de complétion connus (liste blanche globale). */
    public const CRITERIA = ['manual', 'view', 'min_grade', 'vote', 'submit'];

    /**
     * Critères autorisés pour un TYPE d'item donné (liste blanche serveur).
     * min_grade n'a de sens que pour un quiz (relie V1-c / QuizGradeService) ;
     * vote n'a de sens que pour un sondage « choice ».
     *
     * @return array<int, string>
     */
    public static function allowedForType(string $type): array
    {
        return match ($type) {
            'quiz'     => ['min_grade', 'view', 'manual'],
            'choice'   => ['vote', 'view', 'manual'],
            'feedback' => ['submit', 'view', 'manual'],
            default    => ['manual', 'view'],
        };
    }

    /**
     * Critère par défaut d'un type (= comportement HISTORIQUE/naturel) : un quiz se
     * complète à la réussite (min_grade), un sondage en votant (vote), le reste par
     * clic manuel.
     */
    public static function defaultForType(string $type): string
    {
        return match ($type) {
            'quiz'     => 'min_grade',
            'choice'   => 'vote',
            'feedback' => 'submit',
            default    => 'manual',
        };
    }

    /** @return array<int, string> */
    public static function allowedFor(LessonItem $item): array
    {
        return self::allowedForType($item->type);
    }

    public static function defaultFor(LessonItem $item): string
    {
        return self::defaultForType($item->type);
    }

    /**
     * Critère EFFECTIF d'un item : la valeur stockée si elle est dans la liste blanche
     * du type, sinon le défaut du type. Une valeur absente / inconnue / interdite pour
     * le type (ex. min_grade posé sur une vidéo) retombe TOUJOURS sur le défaut.
     */
    public static function criterionFor(LessonItem $item): string
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['completion'] ?? null) : null;

        return in_array($raw, self::allowedFor($item), true)
            ? (string) $raw
            : self::defaultFor($item);
    }

    /**
     * Normalise une valeur de critère reçue pour un type donné en vue du STOCKAGE.
     * Retourne la chaîne à enregistrer dans payload['completion'], ou null si le
     * critère doit rester ABSENT (valeur invalide OU égale au défaut du type →
     * rétrocompat : on ne pollue pas le payload, criterionFor() applique le défaut).
     */
    public static function normalizeForStorage(string $type, mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $allowed = self::allowedForType($type);
        if (! in_array($raw, $allowed, true)) {
            return null;
        }

        return $raw === self::defaultForType($type) ? null : $raw;
    }

    /** Vrai si l'item se complète par un clic manuel de l'étudiant. */
    public static function isManual(LessonItem $item): bool
    {
        return self::criterionFor($item) === 'manual';
    }

    /**
     * Libellé court du MODE d'achèvement (affichage étudiant, a11y). DRY : utilisé par
     * le lecteur pour expliquer comment l'item se complète.
     */
    public static function modeLabel(string $criterion): string
    {
        return match ($criterion) {
            'view'      => 'se complète automatiquement à la consultation',
            'min_grade' => 'se complète en réussissant le quiz',
            'vote'      => 'se complète en votant',
            'submit'    => 'se complète en répondant au sondage',
            default     => 'à marquer comme terminé',
        };
    }

    /**
     * Auto-marque comme COMPLÉTÉS tous les items « view » d'une leçon (idempotent).
     *
     * SÉCURITÉ : appelé UNIQUEMENT par le lecteur après les gardes serveur du
     * LessonController (auth + inscription active réelle + prérequis satisfaits + pas
     * de verrou drip + hors prévisualisation). Ne crée JAMAIS de complétion pour un
     * non-inscrit ni en preview. L'idempotence est garantie par CompletionService::
     * markComplete (une seule ligne par (user, item)).
     */
    public static function autoMarkViewItems(User $user, Lesson $lesson): void
    {
        try {
            $lesson->loadMissing(['lessonItems', 'chapter.course']);
        } catch (\Throwable) {
            return;
        }

        foreach ($lesson->lessonItems as $item) {
            if (self::criterionFor($item) === 'view') {
                CompletionService::markComplete($user, $item);
            }
        }
    }
}
