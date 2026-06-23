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
 *   - adaptive  : feedback IMMÉDIAT AVEC RÉESSAI PÉNALISÉ (parité Moodle « Adaptive
 *                 mode »). Comme l'immédiat, chaque question porte un bouton « Vérifier »
 *                 qui révèle juste/faux ; MAIS un échec ne verrouille PAS la question :
 *                 l'étudiant peut RÉESSAYER, chaque essai raté retranchant une PÉNALITÉ
 *                 (`adaptive_penalty`, défaut 1/3). La question se verrouille dès qu'elle
 *                 est correcte OU au nombre maximal d'essais (`adaptive_max_tries`,
 *                 défaut 3). Le décompte des essais et le calcul de la pénalité sont
 *                 100 % SERVEUR (le client n'envoie jamais la justesse ni la pénalité).
 *
 * Toute valeur inconnue/absente retombe sur 'deferred' (rétrocompat stricte).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

final class QuizBehaviour
{
    public const DEFERRED = 'deferred';

    public const IMMEDIATE = 'immediate';

    public const ADAPTIVE = 'adaptive';

    /** Comportement par défaut = différé (= comportement historique inchangé). */
    public const DEFAULT_BEHAVIOUR = self::DEFERRED;

    /** Pénalité par essai raté par défaut (1/3, parité Moodle « Adaptive mode »). */
    public const DEFAULT_ADAPTIVE_PENALTY = 1 / 3;

    /** Nombre d'essais maximal par défaut en mode adaptatif. */
    public const DEFAULT_ADAPTIVE_MAX_TRIES = 3;

    /**
     * Comportements ACTIFS (liste blanche). Toute autre valeur → DEFAULT_BEHAVIOUR.
     *
     * @var array<int, string>
     */
    public const BEHAVIOURS = [self::DEFERRED, self::IMMEDIATE, self::ADAPTIVE];

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

    /**
     * Vrai si l'item est en mode ADAPTATIF (réessai avec pénalité).
     */
    public static function isAdaptive(mixed $payload): bool
    {
        return self::for($payload) === self::ADAPTIVE;
    }

    /**
     * Vrai si l'item donne une rétroaction PAR QUESTION (immédiat OU adaptatif). Les
     * deux modes partagent le rendu « Vérifier » et la route quiz.verify ; seul le
     * (dé)verrouillage diffère (l'adaptatif autorise le réessai pénalisé).
     */
    public static function isPerQuestion(mixed $payload): bool
    {
        $b = self::for($payload);

        return $b === self::IMMEDIATE || $b === self::ADAPTIVE;
    }

    /**
     * Pénalité ADAPTATIVE par essai raté (fraction des points retranchée à chaque
     * échec). Lecture DÉFENSIVE du payload : valeur hors [0, 1] ou absente → défaut
     * 1/3. Bornée strictement dans [0, 1] (jamais négative, jamais > 1).
     */
    public static function penaltyFor(mixed $payload): float
    {
        $raw = is_array($payload) ? ($payload['adaptive_penalty'] ?? null) : null;

        if (! is_numeric($raw)) {
            return self::DEFAULT_ADAPTIVE_PENALTY;
        }

        return max(0.0, min(1.0, (float) $raw));
    }

    /**
     * Nombre d'essais MAXIMAL en mode adaptatif. Lecture DÉFENSIVE : valeur hors
     * [1, 10] ou absente → défaut 3. La question se verrouille à ce nombre d'essais
     * ratés (borne SERVEUR : un client qui « spamme » Vérifier ne le dépasse pas).
     */
    public static function maxTriesFor(mixed $payload): int
    {
        $raw = is_array($payload) ? ($payload['adaptive_max_tries'] ?? null) : null;

        if (! is_numeric($raw)) {
            return self::DEFAULT_ADAPTIVE_MAX_TRIES;
        }

        return max(1, min(10, (int) $raw));
    }

    /**
     * MULTIPLICATEUR DE PÉNALITÉ pour `n` essais ratés : max(0, 1 - n × pénalité).
     * Source unique (DRY) de la formule, BORNÉE >= 0 (jamais négative). Les points
     * effectifs d'une question = ce multiplicateur × points bruts de justesse.
     */
    public static function penaltyMultiplier(int $failedTries, float $penalty): float
    {
        return max(0.0, 1.0 - max(0, $failedTries) * $penalty);
    }
}
