<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-a - RÉTROACTIONS MULTI-COUCHES du quiz (parité Moodle). SOURCE UNIQUE (DRY)
 * de la sélection et de la normalisation du FEEDBACK GLOBAL par tranche de score
 * (« grade boundaries » Moodle). Lu côté SERVEUR (révision) et utilisé par le
 * CourseEditor pour normaliser/valider les bornes saisies.
 *
 * Le feedback global est porté par payload['overall_feedback'] de l'item quiz :
 *   [ ['min_percent' => 80, 'message' => 'Excellent'], ['min_percent' => 50, 'message' => '…'], … ]
 * Aucune nouvelle table (cohérent avec passing_score, attempts_allowed, grading_method).
 *
 * Les 2 autres couches (feedback par choix + feedback général = explanation de la
 * question) sont portées par la Question / le round et ne nécessitent pas de service.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

final class QuizFeedbackService
{
    /** Longueur max d'un message de feedback global (aligné sur les autres champs texte). */
    public const MESSAGE_MAX = 2000;

    /** Nombre max de bornes de feedback global (garde-fou anti-abus). */
    public const MAX_BOUNDARIES = 12;

    /**
     * Normalise une liste BRUTE de bornes (issue du navigateur) en liste SÛRE :
     *   - chaque entrée doit avoir un message non vide ET un min_percent 0..100 ;
     *   - min_percent borné [0,100], message borné (longueur), trim ;
     *   - dédoublonnage par min_percent (la dernière occurrence gagne) ;
     *   - tri DESC par min_percent (ordre d'usage Moodle) ;
     *   - bornée à MAX_BOUNDARIES.
     * Une liste vide / sans entrée valide → [] (aucune fuite, rétrocompat : pas de clé).
     *
     * @param  array<int|string, mixed>  $raw
     * @return array<int, array{min_percent: int, message: string}>
     */
    public static function normalizeBoundaries(array $raw): array
    {
        $byPercent = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $message = isset($row['message']) && is_string($row['message']) ? trim($row['message']) : '';
            if ($message === '') {
                continue; // une borne sans message est ignorée (pas d'entrée vide).
            }
            $message = mb_substr($message, 0, self::MESSAGE_MAX);

            $rawPercent = $row['min_percent'] ?? null;
            if ($rawPercent === '' || $rawPercent === null || ! is_numeric($rawPercent)) {
                continue; // seuil non renseigné / non numérique → ignoré.
            }
            $minPercent = max(0, min(100, (int) $rawPercent));

            // Dédoublonnage par seuil : la dernière saisie l'emporte (cohérent UI).
            $byPercent[$minPercent] = ['min_percent' => $minPercent, 'message' => $message];
        }

        // Tri DESC par seuil (ordre d'usage : la borne la plus haute en premier).
        krsort($byPercent);

        return array_slice(array_values($byPercent), 0, self::MAX_BOUNDARIES);
    }

    /**
     * Sélectionne le message de feedback global correspondant à un percent donné :
     * la borne la plus haute dont min_percent <= percent. Aucune borne applicable
     * (ou liste vide) → null (rien à afficher). Défensif : tolère une liste non
     * normalisée (re-normalisée ici) pour rester sûr à la lecture du snapshot.
     *
     * @param  array<int|string, mixed>  $boundaries
     */
    public static function messageForPercent(array $boundaries, int $percent): ?string
    {
        $normalized = self::normalizeBoundaries($boundaries);
        if ($normalized === []) {
            return null;
        }

        $p = max(0, min(100, $percent));

        // Déjà trié DESC : la première borne <= percent est la plus haute applicable.
        foreach ($normalized as $boundary) {
            if ($p >= $boundary['min_percent']) {
                return $boundary['message'];
            }
        }

        return null;
    }
}
