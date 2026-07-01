<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gamification MODERNE (capacité LMS 2026) : XP, niveaux, classement PAR COHORTE.
 * Principe de sécurité et d'intégrité identique à BadgeService : l'attribution
 * d'XP est TOUJOURS un calcul SERVEUR déclenché par une VRAIE action pédagogique
 * déjà validée ailleurs (CompletionService, QuizController, ProgressService).
 * Aucune donnée venue du navigateur n'intervient dans le calcul des points.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * BARÈME D'XP (documenté, pondéré par la valeur pédagogique — jamais un « badge
 * pour un clic ») :
 *   - Compléter une leçon/item requis  : POINTS_LESSON_COMPLETED  (10)
 *   - Réussir un quiz (passed=true)     : POINTS_QUIZ_PASSED       (25) — > qu'un
 *     simple clic de complétion, car ça prouve une compréhension réelle.
 *   - Compléter un cours en entier      : POINTS_COURSE_COMPLETED (100)
 *   - Bonus de série (1 jour d'activité): POINTS_STREAK_DAY        (5) — au plus
 *     UNE fois par jour civil (Amérique/Toronto), quel que soit le nombre
 *     d'actions ce jour-là.
 * ─────────────────────────────────────────────────────────────────────────────
 * ANTI-TRICHE / IDEMPOTENCE : chaque crédit passe par award(), qui vérifie
 * l'existence préalable d'une ligne (user_id, source_type, source_id, reason)
 * AVANT toute création (double garde : vérification applicative + contrainte
 * unique en base). Refaire la MÊME action ne crédite donc JAMAIS deux fois.
 * ─────────────────────────────────────────────────────────────────────────────
 * CLASSEMENT : JAMAIS global. `leaderboardForCohort()` / `leaderboardForCourse()`
 * scopent strictement aux membres de LA cohorte / aux inscrits de CE cours.
 * Un apprenant peut se retirer du classement (Loi 25 QC) via
 * setLeaderboardVisibility() — il reste alors invisible aux AUTRES apprenants.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Cohort;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\NotificationPreference;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Models\XpEvent;

final class GamificationService
{
    /** Type de préférence réutilisant academy_notification_preferences (opt-out Loi 25). */
    public const LEADERBOARD_PREF_TYPE = 'gamification_leaderboard';

    public const POINTS_LESSON_COMPLETED = 10;

    public const POINTS_QUIZ_PASSED = 25;

    public const POINTS_COURSE_COMPLETED = 100;

    public const POINTS_STREAK_DAY = 5;

    /** Points requis pour ATTEINDRE un niveau donné (courbe croissante, cf. levelFor()). */
    private const POINTS_PER_LEVEL_STEP = 50;

    /** Drapeau maître — voir config('academy.gamification_enabled'), défaut false. */
    public function isEnabled(): bool
    {
        return (bool) config('academy.gamification_enabled', false);
    }

    // -------------------------------------------------------------------------
    // Points d'entrée métier (appelés depuis CompletionService / QuizController /
    // ProgressService — jamais depuis une route/action cliente directement).
    // -------------------------------------------------------------------------

    /**
     * Crédite l'XP d'une leçon (item) COMPLÉTÉE, puis évalue le bonus de série du
     * jour. Idempotent : re-déclencher pour la même Completion ne crédite rien.
     */
    public function awardLessonCompleted(User $user, Course $course, LessonItem $item, Completion $completion): void
    {
        $this->award($user, $course, 'completion', $completion->id, 'lesson_completed', self::POINTS_LESSON_COMPLETED);
        $this->awardStreakBonusIfNew($user, $course);
    }

    /**
     * Crédite l'XP d'un quiz RÉUSSI. No-op si la tentative n'est pas 'passed'
     * (réussir > cliquer : un échec ou une correction en attente ne crédite rien).
     */
    public function awardQuizPassed(User $user, Course $course, QuizAttempt $attempt): void
    {
        if (! $attempt->passed) {
            return;
        }

        $this->award($user, $course, 'quiz_attempt', $attempt->id, 'quiz_passed', self::POINTS_QUIZ_PASSED);
    }

    /** Crédite l'XP d'un cours COMPLÉTÉ à 100 % (selon son critère configuré). */
    public function awardCourseCompleted(User $user, Course $course): void
    {
        $this->award($user, $course, 'course', $course->id, 'course_completed', self::POINTS_COURSE_COMPLETED);
    }

    /**
     * Bonus de série : au plus UN crédit par utilisateur et par JOUR CIVIL
     * (America/Toronto), quelle que soit la quantité d'actions ce jour-là.
     * La date est encodée dans `reason` (streak_day_YYYY-MM-DD) : c'est ELLE
     * qui garantit l'unicité quotidienne (source_id reste la sentinelle 0).
     */
    private function awardStreakBonusIfNew(User $user, Course $course): void
    {
        $today = now('America/Toronto')->toDateString();

        $this->award($user, $course, 'streak', 0, 'streak_day_'.$today, self::POINTS_STREAK_DAY);
    }

    /**
     * Crédit d'XP GÉNÉRIQUE, idempotent et défensif. No-op si le drapeau est
     * désactivé OU si la ligne (user, source_type, source_id, reason) existe déjà.
     * Un échec (table absente, etc.) ne casse JAMAIS l'action pédagogique appelante.
     */
    public function award(
        User $user,
        ?Course $course,
        string $sourceType,
        int $sourceId,
        string $reason,
        int $points
    ): ?XpEvent {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $alreadyCredited = XpEvent::query()
                ->where('user_id', $user->id)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('reason', $reason)
                ->exists();

            if ($alreadyCredited) {
                return null;
            }

            return XpEvent::create([
                'user_id'     => $user->id,
                'course_id'   => $course?->id,
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
                'reason'      => $reason,
                'points'      => $points,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('[GamificationService] Crédit XP échoué', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Lecture : total, niveau, progression (« Ma progression »).
    // -------------------------------------------------------------------------

    /** Total de points d'un utilisateur (global si $course null, sinon scopé à ce cours). */
    public function totalPointsFor(User $user, ?Course $course = null): int
    {
        try {
            $query = XpEvent::query()->where('user_id', $user->id);

            if ($course !== null) {
                $query->where('course_id', $course->id);
            }

            return (int) $query->sum('points');
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Niveau atteint pour un total de points donné. Courbe croissante volontaire
     * (chaque niveau exige de plus en plus de points) : niveau N atteint dès que
     * total >= 50 * (N-1)². Ex. : 0 → niv.1, 50 → niv.2, 200 → niv.3, 450 → niv.4.
     */
    public function levelFor(int $totalPoints): int
    {
        if ($totalPoints <= 0) {
            return 1;
        }

        return (int) floor(sqrt($totalPoints / self::POINTS_PER_LEVEL_STEP)) + 1;
    }

    /** Points EXACTEMENT requis pour atteindre le niveau donné (inverse de levelFor()). */
    public function pointsForLevel(int $level): int
    {
        $level = max(1, $level);

        return self::POINTS_PER_LEVEL_STEP * ($level - 1) ** 2;
    }

    /**
     * Bloc « Ma progression » : total, niveau courant, palier suivant, % dans le
     * niveau en cours (pour la barre). Scopé $course si fourni (sinon global).
     *
     * @return array{total: int, level: int, points_into_level: int, points_to_next: int, percent: int, next_level_points: int}
     */
    public function progressFor(User $user, ?Course $course = null): array
    {
        $total             = $this->totalPointsFor($user, $course);
        $level             = $this->levelFor($total);
        $currentLevelFloor = $this->pointsForLevel($level);
        $nextLevelPoints   = $this->pointsForLevel($level + 1);
        $span              = max(1, $nextLevelPoints - $currentLevelFloor);
        $pointsIntoLevel   = max(0, $total - $currentLevelFloor);

        return [
            'total'             => $total,
            'level'             => $level,
            'points_into_level' => $pointsIntoLevel,
            'points_to_next'    => max(0, $nextLevelPoints - $total),
            'percent'           => (int) min(100, round(($pointsIntoLevel / $span) * 100)),
            'next_level_points' => $nextLevelPoints,
        ];
    }

    // -------------------------------------------------------------------------
    // Classement — TOUJOURS scopé (cohorte ou, à défaut, cours). Jamais global.
    // -------------------------------------------------------------------------

    /**
     * Classement de LA cohorte donnée (jamais toutes cohortes confondues).
     * Exclut les membres ayant choisi de se retirer (opt-out Loi 25). Trié par
     * points décroissants, rang 1..N attribué APRÈS filtrage (rangs continus).
     *
     * @return Collection<int, array{user: User, points: int, level: int, rank: int}>
     */
    public function leaderboardForCohort(Cohort $cohort): Collection
    {
        $course = $cohort->course;

        return $cohort->members()
            ->get()
            ->filter(fn (User $member): bool => $this->isLeaderboardVisible($member))
            ->map(function (User $member) use ($course): array {
                $points = $this->totalPointsFor($member, $course);

                return [
                    'user'   => $member,
                    'points' => $points,
                    'level'  => $this->levelFor($points),
                ];
            })
            ->sortByDesc('points')
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /**
     * Classement de secours quand l'apprenant n'appartient à AUCUNE cohorte du
     * cours : scopé aux inscrits ACTIFS de ce cours (jamais tous les cours).
     *
     * @return Collection<int, array{user: User, points: int, level: int, rank: int}>
     */
    public function leaderboardForCourse(Course $course): Collection
    {
        $members = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->map(fn (Enrollment $e) => $e->user)
            ->filter();

        return $members
            ->filter(fn (User $member): bool => $this->isLeaderboardVisible($member))
            ->map(function (User $member) use ($course): array {
                $points = $this->totalPointsFor($member, $course);

                return [
                    'user'   => $member,
                    'points' => $points,
                    'level'  => $this->levelFor($points),
                ];
            })
            ->sortByDesc('points')
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /**
     * Cohortes auxquelles appartient l'utilisateur (toutes confondues, pour lui
     * proposer le/les classements pertinents dans son espace). Lecture seule.
     *
     * @return Collection<int, Cohort>
     */
    public function cohortsFor(User $user): Collection
    {
        try {
            return Cohort::query()
                ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->with('course:id,slug,title')
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    // -------------------------------------------------------------------------
    // Opt-out du classement (Loi 25 QC / RGPD) — réutilise
    // academy_notification_preferences (même mécanisme que les préférences de
    // notification courriel, type dédié « gamification_leaderboard »).
    // -------------------------------------------------------------------------

    /** Vrai si l'utilisateur ACCEPTE d'apparaître dans les classements (défaut : oui). */
    public function isLeaderboardVisible(User $user): bool
    {
        try {
            $pref = NotificationPreference::query()
                ->where('user_id', $user->id)
                ->where('type', self::LEADERBOARD_PREF_TYPE)
                ->first();

            if ($pref !== null) {
                return (bool) $pref->enabled;
            }

            return (bool) config('academy.notifications.defaults.'.self::LEADERBOARD_PREF_TYPE, true);
        } catch (\Throwable) {
            return true;
        }
    }

    /** Bascule la visibilité de l'utilisateur COURANT dans les classements. */
    public function setLeaderboardVisibility(User $user, bool $visible): void
    {
        NotificationPreference::updateOrCreate(
            ['user_id' => $user->id, 'type' => self::LEADERBOARD_PREF_TYPE],
            ['enabled' => $visible]
        );
    }
}
