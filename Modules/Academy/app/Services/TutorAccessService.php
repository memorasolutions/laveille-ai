<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TUTEUR IA — Fenêtre d'accès + quota (recommandation de veille juillet 2026).
 *
 * GARDE GLOBALE : tant que le drapeau « academy.ai_tutor_access_control_enabled »
 * est faux (défaut), ce service est un NO-OP complet — aucun grant n'est calculé,
 * aucune restriction n'est appliquée. Le Tuteur IA se comporte EXACTEMENT comme
 * avant l'introduction de cette fonctionnalité.
 *
 * CRITIQUE (zéro effet rétroactif surprise) — la config au niveau COURS
 * (Course::ai_tutor_window_type / ai_tutor_window_days / ai_tutor_fixed_expiry_at)
 * est mutable par le formateur À TOUT MOMENT via l'éditeur de cours, MAIS la date
 * d'expiration effective d'un apprenant déjà inscrit (le « grant ») est CALCULÉE
 * ET FIGÉE au moment de calculateGrantFor() (appelé à l'inscription), JAMAIS
 * recalculée dynamiquement à chaque lecture. Resserrer/élargir la politique du
 * cours après coup ne s'applique donc QU'AUX NOUVELLES inscriptions.
 *
 * Le QUOTA mensuel, lui, est un filet de sécurité coût INDÉPENDANT de la fenêtre :
 * il est relu en direct depuis le cours (pas figé) — seule la fenêtre temporelle
 * est figée par grant.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\TutorAccessGrant;

class TutorAccessService
{
    public const WINDOW_NONE                   = 'none';
    public const WINDOW_RELATIVE_TO_ENROLLMENT = 'relative_to_enrollment';
    public const WINDOW_RELATIVE_TO_LAUNCH     = 'relative_to_course_launch';
    public const WINDOW_FIXED_DATE             = 'fixed_date';

    /** Types de fenêtre valides (liste blanche — formulaire + validation serveur). */
    public const WINDOW_TYPES = [
        self::WINDOW_NONE,
        self::WINDOW_RELATIVE_TO_ENROLLMENT,
        self::WINDOW_RELATIVE_TO_LAUNCH,
        self::WINDOW_FIXED_DATE,
    ];

    public const REASON_OK              = 'ok';
    public const REASON_EXPIRED         = 'expired';
    public const REASON_QUOTA_EXCEEDED  = 'quota_exceeded';

    /**
     * Vrai si le contrôle d'accès (fenêtre + quota) est activé. Source unique de
     * la garde — tout le service et ses appelants passent par cette méthode.
     */
    public function isEnabled(): bool
    {
        return (bool) config('academy.ai_tutor_access_control_enabled', false);
    }

    /**
     * Calcule et FIGE la fenêtre d'accès de cet apprenant pour ce cours, selon la
     * config du cours AU MOMENT DE L'APPEL. IDEMPOTENT : si un grant existe déjà
     * pour (user, course), il est retourné TEL QUEL (jamais recalculé — c'est ce
     * qui garantit l'absence d'effet rétroactif surprise).
     *
     * NO-OP (retourne null) si le drapeau est désactivé : aucun grant limitant
     * n'est créé, l'accès reste illimité (comportement actuel inchangé).
     */
    public function calculateGrantFor(User $user, Course $course): ?TutorAccessGrant
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $existing = TutorAccessGrant::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $startsAt  = now();
        $expiresAt = $this->computeExpiry($course, $startsAt);

        return TutorAccessGrant::create([
            'user_id'                       => $user->id,
            'course_id'                     => $course->id,
            'access_starts_at'              => $startsAt,
            'access_expires_at'             => $expiresAt,
            'questions_used_current_period' => 0,
            'period_reset_at'               => $startsAt->copy()->addMonthNoOverflow(),
        ]);
    }

    /**
     * Vrai/faux + raison (« ok » / « expired » / « quota_exceeded »). NO-OP « ok »
     * si le drapeau est désactivé ou si aucun grant n'a été calculé (ex. staff,
     * cours sans fenêtre) : comportement actuel inchangé (accès illimité).
     *
     * @return array{allowed: bool, reason: string}
     */
    public function isAccessAllowed(User $user, Course $course): array
    {
        if (! $this->isEnabled()) {
            return ['allowed' => true, 'reason' => self::REASON_OK];
        }

        $grant = TutorAccessGrant::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($grant === null) {
            return ['allowed' => true, 'reason' => self::REASON_OK];
        }

        if ($grant->access_expires_at !== null && $grant->access_expires_at->isPast()) {
            return ['allowed' => false, 'reason' => self::REASON_EXPIRED];
        }

        $this->resetPeriodIfNeeded($grant);

        $quota = $course->ai_tutor_monthly_quota;
        if ($quota !== null && $grant->questions_used_current_period >= $quota) {
            return ['allowed' => false, 'reason' => self::REASON_QUOTA_EXCEEDED];
        }

        return ['allowed' => true, 'reason' => self::REASON_OK];
    }

    /**
     * Incrémente le compteur d'usage du quota mensuel (remis à zéro automatiquement
     * si la période est dépassée). NO-OP si le drapeau est désactivé ou si aucun
     * grant n'existe pour cet apprenant/cours (accès illimité, rien à compter).
     */
    public function incrementUsage(User $user, Course $course): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $grant = TutorAccessGrant::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($grant === null) {
            return;
        }

        $this->resetPeriodIfNeeded($grant);

        $grant->increment('questions_used_current_period');
    }

    /**
     * Grants actifs (fenêtre non nulle et non expirée) — utilisés par la commande
     * de rappel J-7 / J-1. LECTURE SEULE, ne modifie rien.
     *
     * @return \Illuminate\Support\Collection<int, TutorAccessGrant>
     */
    public function grantsWithUpcomingExpiry(): \Illuminate\Support\Collection
    {
        return TutorAccessGrant::query()
            ->whereNotNull('access_expires_at')
            ->where('access_expires_at', '>=', now())
            ->with(['user', 'course'])
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Outils internes
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Traduit la config du cours (au moment de l'appel) en date d'expiration
     * absolue, ou null (illimité). AUCUNE écriture ici : le résultat n'est figé
     * que par l'appelant (calculateGrantFor), jamais recalculé ensuite.
     */
    private function computeExpiry(Course $course, Carbon $startsAt): ?Carbon
    {
        $type = $course->ai_tutor_window_type ?? self::WINDOW_NONE;

        return match ($type) {
            self::WINDOW_RELATIVE_TO_ENROLLMENT => $course->ai_tutor_window_days
                ? $startsAt->copy()->addDays((int) $course->ai_tutor_window_days)
                : null,
            self::WINDOW_RELATIVE_TO_LAUNCH => $course->ai_tutor_window_days
                ? ($course->published_at ?? $course->created_at ?? $startsAt)->copy()->addDays((int) $course->ai_tutor_window_days)
                : null,
            self::WINDOW_FIXED_DATE => $course->ai_tutor_fixed_expiry_at,
            default                 => null, // WINDOW_NONE => illimité
        };
    }

    /** Remet le compteur du quota à zéro si la période courante est dépassée. */
    private function resetPeriodIfNeeded(TutorAccessGrant $grant): void
    {
        if ($grant->period_reset_at !== null && $grant->period_reset_at->isPast()) {
            $grant->questions_used_current_period = 0;
            $grant->period_reset_at               = now()->addMonthNoOverflow();
            $grant->save();
        }
    }
}
