<?php

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\QuizAttempt;

/**
 * Service de calcul du score de risque d'abandon pour les apprenants (heuristique
 * déterministe, sans dépendance ML externe).
 *
 * Le score 0-100 est la somme de trois composantes pondérées :
 *   - inactivité (jusqu'à WEIGHT_INACTIVITY_MAX points),
 *   - échecs quiz consécutifs (jusqu'à WEIGHT_QUIZ_FAILURES_MAX points),
 *   - stagnation de la progression (jusqu'à WEIGHT_STAGNATION_MAX points).
 *
 * Chaque pondération est une constante nommée et documentée pour faciliter
 * les ajustements sans toucher à la logique.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
final class RiskScoreService
{
    // ─── Pondérations maximales (somme = 100) ─────────────────────────────────
    public const WEIGHT_INACTIVITY_MAX = 40;
    public const WEIGHT_QUIZ_FAILURES_MAX = 30;
    public const WEIGHT_STAGNATION_MAX = 30;

    // ─── Seuils d'inactivité (jours) ──────────────────────────────────────────
    public const INACTIVITY_DAYS_HIGH = 14;
    public const INACTIVITY_DAYS_MED  = 7;

    // ─── Seuils d'échecs quiz consécutifs ────────────────────────────────────
    public const QUIZ_CONSECUTIVE_FAILURES_HIGH = 3;
    public const QUIZ_CONSECUTIVE_FAILURES_MED  = 2;

    // ─── Stagnation ─────────────────────────────────────────────────────────
    /** Progression attendue par semaine depuis l'inscription (en %). */
    public const STAGNATION_EXPECTED_PERCENT_PER_WEEK = 10;

    /**
     * Calcule le score de risque pour UN apprenant dans UN cours.
     *
     * Les données sont scopées à (userId, course_id) — anti-IDOR : l'appelant
     * est responsable de vérifier que userId == auth()->id() côté Livewire.
     *
     * @return array{
     *   score: int,
     *   level: string,
     *   reasons: string[],
     *   details: array{
     *     inactivity_score: int,
     *     quiz_score: int,
     *     stagnation_score: int,
     *     days_inactive: int,
     *     consecutive_fails: int,
     *     weeks_enrolled: float,
     *     percent: int,
     *   }
     * }
     */
    public function scoreForEnrollee(int $userId, Course $course): array
    {
        $enrollment = Enrollment::query()
            ->where('user_id', $userId)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if ($enrollment === null) {
            return $this->zeroResult();
        }

        $progress = Progress::query()
            ->where('user_id', $userId)
            ->where('course_id', $course->id)
            ->first();

        $percent = (int) ($progress?->percent ?? 0);

        // Un apprenant à 100 % n'est jamais à risque.
        if ($percent >= 100) {
            return $this->zeroResult(percent: 100);
        }

        [$inactivityScore, $daysInactive] = $this->inactivityScore($enrollment, $progress);
        [$quizScore, $consecutiveFails]   = $this->quizScore($userId, $course);
        [$stagnationScore, $weeksEnrolled, $expectedPercent] = $this->stagnationScore($enrollment, $percent);

        $total = min(100, $inactivityScore + $quizScore + $stagnationScore);
        $level = match (true) {
            $total >= 67 => 'élevé',
            $total >= 34 => 'moyen',
            default      => 'faible',
        };

        $reasons = [];
        if ($inactivityScore > 0) {
            $reasons[] = "Inactif depuis {$daysInactive} jours";
        }
        if ($quizScore > 0) {
            $reasons[] = "{$consecutiveFails} échec(s) quiz consécutif(s)";
        }
        if ($stagnationScore > 0) {
            $ep = (int) round($expectedPercent);
            $reasons[] = "Progression en retard ({$percent}% accompli, {$ep}% attendu)";
        }

        return [
            'score'   => $total,
            'level'   => $level,
            'reasons' => $reasons,
            'details' => [
                'inactivity_score'  => $inactivityScore,
                'quiz_score'        => $quizScore,
                'stagnation_score'  => $stagnationScore,
                'days_inactive'     => $daysInactive,
                'consecutive_fails' => $consecutiveFails,
                'weeks_enrolled'    => $weeksEnrolled,
                'percent'           => $percent,
            ],
        ];
    }

    /**
     * Calcule les scores pour TOUS les inscrits actifs d'un cours.
     * Scopé course_id (anti-IDOR). Trié par score décroissant.
     *
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function scoresForCourse(Course $course): \Illuminate\Support\Collection
    {
        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->with(['user:id,name,email'])
            ->get();

        return $enrollments
            ->map(function (Enrollment $enrollment) use ($course): array {
                $user = $enrollment->user;
                $data = $this->scoreForEnrollee($enrollment->user_id, $course);

                return array_merge([
                    'user_id' => (int) $enrollment->user_id,
                    'name'    => (string) ($user?->name ?? 'Apprenant'),
                    'email'   => (string) ($user?->email ?? ''),
                ], $data);
            })
            ->sortByDesc('score')
            ->values(); // @phpstan-ignore-line return.type
    }

    /**
     * Vue organisationnelle (admin) : agrège tous les inscrits actifs de tous les
     * cours. Requêtes SQL sans fonctions MySQL/PG spécifiques (compatible SQLite).
     *
     * @return array{
     *   total_enrolled: int,
     *   at_risk_count: int,
     *   high_risk_count: int,
     *   avg_completion_rate: int,
     *   courses_summary: list<array{course_id: int, course_title: string, enrolled: int, at_risk: int, avg_score: int}>,
     * }
     */
    public function organisationSummary(): array
    {
        $enrollments = Enrollment::query()
            ->where('status', 'active')
            ->with(['course:id,title', 'user:id'])
            ->get();

        if ($enrollments->isEmpty()) {
            return [
                'total_enrolled'      => 0,
                'at_risk_count'       => 0,
                'high_risk_count'     => 0,
                'avg_completion_rate' => 0,
                'courses_summary'     => [],
            ];
        }

        $totalEnrolled  = $enrollments->count();
        $atRiskCount    = 0;
        $highRiskCount  = 0;
        $completionSum  = 0;
        $courseStats    = [];

        foreach ($enrollments as $enrollment) {
            $course  = $enrollment->course;
            if ($course === null) {
                continue;
            }
            $courseId = (int) $course->id;

            if (! isset($courseStats[$courseId])) {
                $courseStats[$courseId] = [
                    'course_id'    => $courseId,
                    'course_title' => (string) ($course->title ?? "Cours #{$courseId}"),
                    'enrolled'     => 0,
                    'at_risk'      => 0,
                    'score_sum'    => 0,
                ];
            }

            $courseStats[$courseId]['enrolled']++;

            $data  = $this->scoreForEnrollee($enrollment->user_id, $course);
            $score = $data['score'];
            $pct   = $data['details']['percent'];

            $completionSum += $pct;
            $courseStats[$courseId]['score_sum'] += $score;

            if ($score >= 34) {
                $atRiskCount++;
                $courseStats[$courseId]['at_risk']++;
            }
            if ($score >= 67) {
                $highRiskCount++;
            }
        }

        $avgCompletionRate = $totalEnrolled > 0
            ? (int) round($completionSum / $totalEnrolled)
            : 0;

        $coursesSummary = array_values(array_map(
            static function (array $stats): array {
                /** @phpstan-ignore greater.alwaysTrue */
                $avg = $stats['enrolled'] > 0
                    ? (int) round($stats['score_sum'] / $stats['enrolled'])
                    : 0;

                return [
                    'course_id'    => $stats['course_id'],
                    'course_title' => $stats['course_title'],
                    'enrolled'     => $stats['enrolled'],
                    'at_risk'      => $stats['at_risk'],
                    'avg_score'    => $avg,
                ];
            },
            $courseStats,
        ));

        usort(
            $coursesSummary,
            static fn (array $a, array $b): int => $b['at_risk'] <=> $a['at_risk'],
        );

        return [
            'total_enrolled'      => $totalEnrolled,
            'at_risk_count'       => $atRiskCount,
            'high_risk_count'     => $highRiskCount,
            'avg_completion_rate' => $avgCompletionRate,
            'courses_summary'     => $coursesSummary,
        ];
    }

    // ─── Calculs des composantes ────────────────────────────────────────────

    /**
     * Score d'inactivité.
     *
     * @return array{int, int}  [score, jours_inactif]
     */
    private function inactivityScore(Enrollment $enrollment, ?Progress $progress): array
    {
        $lastActivity = $progress?->last_activity_at ?? $enrollment->enrolled_at;

        if ($lastActivity === null) {
            return [0, 0];
        }

        // Ordre : passé → présent pour obtenir un entier positif.
        $days = (int) Carbon::parse($lastActivity)->startOfDay()->diffInDays(
            Carbon::now()->startOfDay(),
        );
        $days = max(0, $days);

        $score = (int) min(
            self::WEIGHT_INACTIVITY_MAX,
            round(($days / self::INACTIVITY_DAYS_HIGH) * self::WEIGHT_INACTIVITY_MAX),
        );

        return [$score, $days];
    }

    /**
     * Score basé sur les échecs consécutifs aux quiz.
     *
     * Compte les tentatives les plus récentes en ordre desc jusqu'au 1er succès.
     *
     * @return array{int, int}  [score, nb_echecs_consecutifs]
     */
    private function quizScore(int $userId, Course $course): array
    {
        $attempts = QuizAttempt::query()
            ->where('user_id', $userId)
            ->where('course_id', $course->id)
            ->orderByDesc('submitted_at')
            ->get(['passed']);

        $consecutiveFails = 0;
        foreach ($attempts as $attempt) {
            if ($attempt->passed) {
                break;
            }
            $consecutiveFails++;
        }

        $score = 0;
        if ($consecutiveFails >= self::QUIZ_CONSECUTIVE_FAILURES_HIGH) {
            $score = self::WEIGHT_QUIZ_FAILURES_MAX;
        } elseif ($consecutiveFails >= self::QUIZ_CONSECUTIVE_FAILURES_MED) {
            $score = (int) round(self::WEIGHT_QUIZ_FAILURES_MAX / 2);
        } elseif ($consecutiveFails === 1) {
            // 1 seul échec consécutif : score partiel proportionnel.
            $score = (int) round(
                (1 / self::QUIZ_CONSECUTIVE_FAILURES_HIGH) * self::WEIGHT_QUIZ_FAILURES_MAX,
            );
        }

        return [$score, $consecutiveFails];
    }

    /**
     * Score de stagnation : écart entre la progression réelle et la progression
     * attendue en fonction du temps écoulé depuis l'inscription.
     *
     * @return array{int, float, float}  [score, semaines_inscrit, pct_attendu]
     */
    private function stagnationScore(Enrollment $enrollment, int $currentPercent): array
    {
        if ($enrollment->enrolled_at === null) {
            return [0, 0.0, 0.0];
        }

        // Ordre passé → présent pour un résultat positif.
        $diffDays     = (float) Carbon::parse($enrollment->enrolled_at)->startOfDay()->diffInDays(Carbon::now()->startOfDay());
        $weeksEnrolled = max(0.1, $diffDays / 7.0);

        $expectedPercent = min(100.0, $weeksEnrolled * self::STAGNATION_EXPECTED_PERCENT_PER_WEEK);

        if ($currentPercent >= $expectedPercent || $expectedPercent <= 0.0) {
            return [0, $weeksEnrolled, $expectedPercent];
        }

        $gap   = $expectedPercent - $currentPercent;
        $score = (int) min(
            self::WEIGHT_STAGNATION_MAX,
            round(($gap / $expectedPercent) * self::WEIGHT_STAGNATION_MAX),
        );

        return [$score, $weeksEnrolled, $expectedPercent];
    }

    /**
     * Résultat neutre (score 0, risque faible).
     *
     * @return array<string, mixed>
     */
    private function zeroResult(int $percent = 0): array
    {
        return [
            'score'   => 0,
            'level'   => 'faible',
            'reasons' => [],
            'details' => [
                'inactivity_score'  => 0,
                'quiz_score'        => 0,
                'stagnation_score'  => 0,
                'days_inactive'     => 0,
                'consecutive_fails' => 0,
                'weeks_enrolled'    => 0.0,
                'percent'           => $percent,
            ],
        ];
    }
}
