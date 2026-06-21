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
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\UserBadge;

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

        $percent = (int) (Progress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->value('percent') ?? 0);

        return [$percent >= 100, $course->id];
    }

    /** Nombre de cours complétés (Progress.percent >= 100). */
    private function countCompletedCourses(User $user): int
    {
        return Progress::query()
            ->where('user_id', $user->id)
            ->where('percent', '>=', 100)
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
     * Réel : QuizController stocke dans Completion.score le NOMBRE de bonnes
     * réponses (pas un pourcentage). Un sans-faute = score égal au nombre de
     * questions du quiz (item->payload['questions']), avec au moins une question.
     * On évalue défensivement : aucune confiance au client, lecture des items
     * de type quiz complétés par l'utilisateur.
     */
    private function hasPerfectQuiz(User $user): bool
    {
        $completions = Completion::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->with('lessonItem')
            ->get();

        foreach ($completions as $completion) {
            $item = $completion->lessonItem;

            if ($item === null || $item->type !== 'quiz') {
                continue;
            }

            $questions = is_array($item->payload['questions'] ?? null)
                ? $item->payload['questions']
                : [];
            $total = count($questions);

            if ($total > 0 && (int) $completion->score === $total) {
                return true;
            }
        }

        return false;
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
