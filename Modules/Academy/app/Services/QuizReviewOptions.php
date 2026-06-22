<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-d — OPTIONS DE RÉVISION (parité Moodle, volet « What ») : contrôle CE QUE la
 * révision V1-a affiche après soumission (le « quand » reste hors périmètre =
 * différé après soumission, comme aujourd'hui).
 *
 * Helper DRY pour lire les toggles avec un DÉFAUT « tout vrai » : un item SANS
 * payload['review_options'] (ou avec une clé absente) se comporte EXACTEMENT comme
 * la révision V1-a actuelle (rétrocompat stricte). Toute valeur est normalisée en
 * booléen ; une clé inconnue est ignorée ; une valeur absente → true.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

final class QuizReviewOptions
{
    /**
     * Clés reconnues = portions de la révision V1-a, toutes affichées par défaut.
     *
     * @var array<int, string>
     */
    public const KEYS = [
        'show_correctness',       // « ✔ Bonne réponse » / « ✗ À revoir »
        'show_marks',             // points obtenus (réservé : la révision V1-a n'affiche pas encore les points par question)
        'show_specific_feedback', // couche 1 : rétroaction du choix sélectionné
        'show_general_feedback',  // couche 2 : explication générale de la question
        'show_overall_feedback',  // couche 3 : feedback global par tranche de score
        'show_right_answer',      // « Bonne réponse : … »
    ];

    /**
     * Normalise le payload['review_options'] en map booléenne complète (toutes les
     * clés présentes), défaut true.
     *
     * @param  mixed $raw  payload['review_options'] (array attendu, sinon ignoré)
     * @return array<string, bool>
     */
    public static function normalize(mixed $raw): array
    {
        $options = is_array($raw) ? $raw : [];
        $result  = [];

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $options)) {
                $result[$key] = true; // défaut = affiché (rétrocompat V1-a)

                continue;
            }

            $result[$key] = filter_var(
                $options[$key],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? true;
        }

        return $result;
    }

    /**
     * Lit un toggle précis avec défaut true.
     */
    public static function show(mixed $raw, string $key): bool
    {
        $map = self::normalize($raw);

        return $map[$key] ?? true;
    }
}
