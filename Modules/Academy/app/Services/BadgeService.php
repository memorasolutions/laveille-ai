<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Attribution des badges / jalons d'engagement (Phase E / E1).
 *
 * Principe de sécurité : l'attribution est TOUJOURS un calcul SERVEUR à partir
 * des données réelles de l'utilisateur (Progress, Completion, CertificateIssued).
 * Aucune donnée venue du navigateur n'intervient. L'idempotence est garantie
 * par la contrainte unique du pivot + firstOrCreate : ré-évaluer ne crée jamais
 * de doublon et n'a aucun effet de bord si le badge est déjà gagné.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Badge;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\UserBadge;
use Modules\Academy\Services\CourseCompletionService;

final class BadgeService
{
    /**
     * Évalue tous les badges actifs pour un utilisateur et décerne ceux dont le
     * critère est rempli. Retourne UNIQUEMENT les badges NOUVELLEMENT décernés.
     *
     * @param  User        $user    Utilisateur évalué (source de vérité serveur).
     * @param  Course|null $course  Cours en contexte (pour les badges liés à un cours).
     * @return Collection<int, Badge> Badges fraîchement décernés.
     */
    public function evaluateForUser(User $user, ?Course $course = null): Collection
    {
        $newlyAwarded = collect();

        // Table absente (migration non lancée) → no-op défensif, ne casse rien.
        try {
            $badges = Badge::query()->active()->get();
        } catch (\Throwable) {
            return $newlyAwarded;
        }

        foreach ($badges as $badge) {
            try {
                $awarded = $this->evaluateBadge($user, $badge, $course);

                if ($awarded instanceof Badge) {
                    $newlyAwarded->push($awarded);
                }
            } catch (\Throwable) {
                // Un badge en erreur ne doit jamais bloquer les autres ni la complétion.
            }
        }

        return $newlyAwarded;
    }

    /**
     * Évalue UN badge. Si le critère est rempli et le badge pas encore gagné,
     * l'attribue (firstOrCreate = idempotent) et le retourne. Sinon null.
     *
     * @return Badge|null Le badge si nouvellement décerné, null sinon.
     */
    private function evaluateBadge(User $user, Badge $badge, ?Course $course): ?Badge
    {
        [$earned, $courseId] = $this->meetsCriteria($user, $badge, $course);

        if (! $earned) {
            return null;
        }

        // firstOrCreate sur le pivot : IDEMPOTENT. Si la ligne existe déjà (même
        // badge_id/user_id/course_id), on ne crée rien et le badge n'est PAS
        // compté comme « nouvellement décerné ».
        $pivot = UserBadge::firstOrCreate(
            [
                'badge_id'  => $badge->id,
                'user_id'   => $user->id,
                'course_id' => $courseId,
            ],
            [
                'awarded_at' => now(),
            ]
        );

        if (! $pivot->wasRecentlyCreated) {
            return null;
        }

        // Événement défensif (extensible : notification, activity log…).
        try {
            event('academy.badge.awarded', [$user, $badge, $pivot]);
        } catch (\Throwable) {
            // Silencieux.
        }

        return $badge;
    }

    /**
     * Le critère du badge est-il rempli pour cet utilisateur ?
     * Renvoie [bool $earned, ?int $courseId] : le course_id du pivot quand le
     * badge est lié à un cours précis (sinon null = badge global).
     *
     * @return array{0: bool, 1: int|null}
     */
    private function meetsCriteria(User $user, Badge $badge, ?Course $course): array
    {
        return match ($badge->criteria_type) {
            'first_course_completed' => [$this->countCompletedCourses($user) >= 1, null],
            'course_completed'       => $this->evaluateCourseCompleted($user, $course),
            'lessons_completed'      => [
                $this->countCompletedLessons($user) >= (int) ($badge->criteria_value ?? 0)
                    && (int) ($badge->criteria_value ?? 0) > 0,
                null,
            ],
            'first_certificate'      => [$this->countCertificates($user) >= 1, null],
            'perfect_quiz'           => [$this->hasPerfectQuiz($user), null],
            default                  => [false, null],
        };
    }

    /**
     * course_completed : le cours EN CONTEXTE est-il complété à 100 % ? Lié au cours.
     *
     * @return array{0: bool, 1: int|null}
     */
    private function evaluateCourseCompleted(User $user, ?Course $course): array
    {
        if ($course === null) {
            return [false, null];
        }

        // Complétion SELON le critère configuré du cours (source unique). Défaut
        // « all_required » ⇒ percent ≥ 100 ⇒ comportement historique préservé.
        $complete = (new CourseCompletionService())->isComplete($user, $course);

        return [$complete, $course->id];
    }

    /**
     * Nombre de cours COMPLÉTÉS par l'utilisateur, selon le critère propre à chaque
     * cours (CourseCompletionService). On parcourt ses lignes de progression (petit N)
     * et on délègue la décision au service. Défaut « all_required » ⇒ percent ≥ 100 ⇒
     * identique au comportement historique (rétrocompat).
     */
    private function countCompletedCourses(User $user): int
    {
        $svc = new CourseCompletionService();

        return Progress::query()
            ->where('user_id', $user->id)
            ->with('course')
            ->get()
            ->filter(fn (Progress $p): bool => $p->course !== null && $svc->isComplete($user, $p->course))
            ->count();
    }

    /** Nombre de leçons (items) complétées par l'utilisateur, tous cours confondus. */
    private function countCompletedLessons(User $user): int
    {
        return Completion::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
    }

    /** Nombre de certificats émis pour l'utilisateur. */
    private function countCertificates(User $user): int
    {
        return CertificateIssued::query()
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * L'utilisateur a-t-il au moins un quiz réussi PARFAITEMENT ?
     *
     * Fix B03 : lit QuizAttempt (source de vérité réelle) au lieu de
     * payload['questions'] (vide pour les quiz bank/QT — cf. QuizService::buildRound).
     * Condition : tentative réussie (passed=true), score ÉGAL à max_score,
     * et au moins une question (max_score > 0). Lecture SERVEUR uniquement.
     *
     * // ACTION: fix B03 — hasPerfectQuiz lit QuizAttempt.score/max_score
     * // SELF: remplacement ciblé <20 lignes
     * // RAISON: payload['questions'] vide sur les quiz bank/QT → $total=0 → badge jamais décerné
     */
    private function hasPerfectQuiz(User $user): bool
    {
        return QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->whereColumn('score', 'max_score')
            ->where('max_score', '>', 0)
            ->exists();
    }

    /**
     * Badges GAGNÉS par un utilisateur (pour l'affichage). Strictement scopé à
     * son user_id (anti-IDOR). Eager-load du badge, le plus récent d'abord.
     *
     * @return Collection<int, UserBadge>
     */
    public function earnedFor(User $user): Collection
    {
        try {
            return UserBadge::query()
                ->forUser($user->id)
                ->with('badge')
                ->orderByDesc('awarded_at')
                ->orderByDesc('id')
                ->get()
                ->filter(fn (UserBadge $ub): bool => $ub->badge !== null && $ub->badge->is_active)
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }
}
