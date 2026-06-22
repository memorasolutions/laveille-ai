<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-f - COMPORTEMENT DE QUESTION (parité Moodle « how questions behave »). SOURCE
 * UNIQUE (DRY) de la lecture/normalisation du mode de rétroaction d'un item quiz,
 * porté par payload['question_behaviour'] (comme passing_score, grading_method,
 * review_options). Aucune nouvelle table.
 *
 * Modes (liste blanche stricte, défaut Moodle/historique = 'deferred') :
 *   - deferred  : feedback DIFFÉRÉ (1 soumission, révision à la fin) = comportement
 *                 ACTUEL strictement inchangé (rétrocompat : item sans la clé).
 *   - immediate : feedback IMMÉDIAT (l'étudiant valide UNE question, voit aussitôt sa
 *                 justesse + rétroaction, puis la question est verrouillée).
 *
 * 'adaptive' (réessai avec pénalité) n'est PAS activé (reporté, cf. rapport V1-f) :
 * il n'apparaît donc pas dans BEHAVIOURS et toute valeur inconnue retombe sur
 * 'deferred'.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

final class QuizBehaviour
{
    public const DEFERRED = 'deferred';

    public const IMMEDIATE = 'immediate';

    /** Comportement par défaut = différé (= comportement historique inchangé). */
    public const DEFAULT_BEHAVIOUR = self::DEFERRED;

    /**
     * Comportements ACTIFS (liste blanche). Toute autre valeur → DEFAULT_BEHAVIOUR.
     *
     * @var array<int, string>
     */
    public const BEHAVIOURS = [self::DEFERRED, self::IMMEDIATE];

    /**
     * Mode de rétroaction effectif d'un item quiz.
     *
     * @param  mixed $payload  payload de l'item (array) OU directement la valeur brute.
     *                         Une valeur absente/inconnue → 'deferred' (rétrocompat).
     */
    public static function for(mixed $payload): string
    {
        $raw = is_array($payload)
            ? ($payload['question_behaviour'] ?? null)
            : $payload;

        if (is_string($raw) && in_array($raw, self::BEHAVIOURS, true)) {
            return $raw;
        }

        return self::DEFAULT_BEHAVIOUR;
    }

    /**
     * Vrai si l'item est en mode rétroaction IMMÉDIATE.
     */
    public static function isImmediate(mixed $payload): bool
    {
        return self::for($payload) === self::IMMEDIATE;
    }
}
