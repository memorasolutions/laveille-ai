<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ESSAI - CORRECTION MANUELLE (type Moodle « Essay »). SOURCE UNIQUE (DRY) du
 * RECALCUL d'une tentative qui contient au moins un essai, et de la FINALISATION
 * (pose de la complétion) une fois tous les essais corrigés.
 *
 * Invariant clé : le scoring AUTO n'est jamais dupliqué. On re-passe le round
 * snapshoté (questions_snapshot) + les réponses (answers) dans QuizService::score()
 * pour obtenir les points AUTO (les essais y rapportent 0) et le total possible
 * (qui INCLUT déjà les points des essais). On ajoute ensuite les points MANUELS
 * attribués par le formateur (bornés au barème de chaque essai), d'où la note FINALE.
 *
 * Tout est lu côté SERVEUR uniquement ; l'appelant (EssayGrading Livewire) est gâté
 * manageEnrollments et re-résout la tentative scopée au cours (anti-IDOR).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Modules\Academy\Models\QuizAttempt;

final class EssayGradingService
{
    /**
     * Indices des essais présents dans le round snapshoté d'une tentative.
     *
     * @return array<int, int>
     */
    public static function essayIndexes(QuizAttempt $attempt): array
    {
        $questions = is_array($attempt->questions_snapshot) ? $attempt->questions_snapshot : [];
        $indexes   = [];
        foreach ($questions as $i => $q) {
            if (is_array($q) && ($q['type'] ?? null) === 'essai') {
                $indexes[] = (int) $i;
            }
        }

        return $indexes;
    }

    /**
     * Barème (points max) d'un essai donné, lu dans le round snapshoté (jamais le client).
     * Défaut 1 si absent/illisible (cohérent avec QuizService::score).
     */
    public static function essayMaxPoints(QuizAttempt $attempt, int $index): int
    {
        $questions = is_array($attempt->questions_snapshot) ? $attempt->questions_snapshot : [];
        $q         = $questions[$index] ?? ($questions[(string) $index] ?? null);

        if (! is_array($q) || ($q['type'] ?? null) !== 'essai') {
            return 1;
        }

        $points = isset($q['points']) && (int) $q['points'] >= 1 ? (int) $q['points'] : 1;

        return min(100, $points);
    }

    /**
     * Tous les essais de la tentative ont-ils reçu une note manuelle ?
     * (un essai est « corrigé » dès qu'une clé numérique existe dans manual_scores).
     */
    public static function allEssaysGraded(QuizAttempt $attempt, ?array $manualScores = null): bool
    {
        $manual  = $manualScores ?? (is_array($attempt->manual_scores) ? $attempt->manual_scores : []);
        $indexes = self::essayIndexes($attempt);

        if ($indexes === []) {
            return true;
        }

        foreach ($indexes as $i) {
            if (! array_key_exists((string) $i, $manual) && ! array_key_exists($i, $manual)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recalcule la note d'une tentative (auto + manuel) à partir d'un jeu de scores
     * manuels donné (sans rien écrire). Les points manuels sont BORNÉS au barème de
     * chaque essai (défense en profondeur, même si l'appelant valide déjà).
     *
     * @param  array<int|string, mixed>  $manualScores  {index_essai: points}
     * @return array{points_earned: float|int, points_possible: int, percent: int, correct: int, auto: array<string, mixed>}
     */
    public static function recompute(QuizAttempt $attempt, ?array $manualScores = null): array
    {
        $questions = is_array($attempt->questions_snapshot) ? $attempt->questions_snapshot : [];
        $answers   = is_array($attempt->answers) ? $attempt->answers : [];
        $manual    = $manualScores ?? (is_array($attempt->manual_scores) ? $attempt->manual_scores : []);

        // Scoring AUTO (les essais rapportent 0 ; possible inclut leurs points).
        $auto     = QuizService::score($questions, $answers);
        $possible = (int) $auto['points_possible'];

        // Somme des points manuels, BORNÉS [0, barème de l'essai].
        $manualSum = 0.0;
        foreach (self::essayIndexes($attempt) as $i) {
            $raw = $manual[$i] ?? ($manual[(string) $i] ?? null);
            if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                continue;
            }
            $pts       = max(0.0, min((float) self::essayMaxPoints($attempt, $i), (float) $raw));
            $manualSum += $pts;
        }

        $earned  = (float) $auto['points_earned'] + $manualSum;
        $percent = $possible > 0 ? (int) round(($earned / $possible) * 100) : 0;

        // RÉTROCOMPAT d'affichage : un total entier reste un int (cf. QuizService).
        $earnedDisplay = round($earned, 2);
        if ($earnedDisplay === (float) (int) $earnedDisplay) {
            $earnedDisplay = (int) $earnedDisplay;
        }

        return [
            'points_earned'   => $earnedDisplay,
            'points_possible' => $possible,
            'percent'         => $percent,
            'correct'         => (int) $auto['correct'],
            'auto'            => $auto,
        ];
    }
}
