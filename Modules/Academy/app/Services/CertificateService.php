<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Progress;
use Modules\Academy\Services\CourseCompletionService;

final class CertificateService
{
    /**
     * Émet un certificat pour l'utilisateur sur le cours donné, uniquement si le cours
     * est COMPLÉTÉ selon son critère configuré (CourseCompletionService, source unique).
     * Défaut « all_required » ⇒ 100 % des items requis ⇒ comportement historique préservé.
     * Idempotent : si un certificat existe déjà (user+course), le retourne sans recréer.
     * Retourne null si le cours n'est pas complété (jamais d'exception métier).
     */
    public function issueFor(User $user, Course $course): ?CertificateIssued
    {
        // Garde de complétion configurable (remplace l'ancien percent === 100 codé en dur).
        if (! (new CourseCompletionService())->isComplete($user, $course)) {
            return null;
        }

        // Progression persistée (pour les heures). Peut être absente si la complétion
        // est atteinte par un critère hors items requis (ex. min_grade).
        $progress = Progress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        // V5a-2 : final_score selon le critère configuré du cours (source unique).
        // Pour min_grade : note finale du carnet (peut être élevée avec 0 items requis).
        // Pour les autres critères : ratio items/total (comportement historique).
        $criteria   = CourseCompletionService::criteriaFor($course);
        $finalScore = 100;
        if ($criteria['type'] === CourseCompletionService::TYPE_MIN_GRADE) {
            try {
                $grade = (new CourseCompletionService())->finalGradePercent($user, $course);
                if ($grade !== null) {
                    $finalScore = (int) round(max(0.0, min(100.0, $grade)));
                }
            } catch (\Throwable) {
                $finalScore = 100;
            }
        } elseif ($progress !== null && $progress->required_total > 0) {
            $finalScore = (int) round($progress->required_completed / $progress->required_total * 100);
        }

        // Champs uniques générés AVANT la transaction (pas de side-effect DB ici).
        $serial           = 'ACAD-' . strtoupper(substr(md5(uniqid((string) $user->id . (string) $course->id, true)), 0, 12));
        $verificationHash = hash('sha256', $user->id . $course->id . uniqid('', true) . config('app.key', ''));
        $publicUrlSlug    = 'cert-' . substr($verificationHash, 0, 16) . '-' . time();
        $hoursEarned      = (int) ceil(($course->duration_minutes ?? 0) / 60);
        $scoreForClosure  = $finalScore;

        // V5a-3 : race condition - firstOrCreate dans une transaction. L'index unique
        // (user_id, course_id) (migration additive) empêche tout doublon même sous
        // concurrence : Laravel catchera la UniqueConstraintViolationException et
        // renverra la ligne existante.
        $certificate = DB::transaction(
            static function () use ($user, $course, $serial, $verificationHash, $publicUrlSlug, $hoursEarned, $scoreForClosure): CertificateIssued {
                return CertificateIssued::firstOrCreate(
                    ['user_id' => $user->id, 'course_id' => $course->id],
                    [
                        'serial'            => $serial,
                        'verification_hash' => $verificationHash,
                        'public_url_slug'   => $publicUrlSlug,
                        'issued_at'         => now(),
                        'hours_earned'      => $hoursEarned,
                        'final_score'       => $scoreForClosure,
                    ]
                );
            }
        );

        // ACTION: fix BUG-B02b — guard activity log + event sur wasRecentlyCreated
        // SELF: 3 lignes modifiées
        // RAISON: sans garde, chaque re-appel de issueFor (ex. ProgressService::recalculate
        //         à chaque item complété après la certification) écrivait 1-2 entrées
        //         supplémentaires dans activity_log (même causer_id, même subject_id, même
        //         seconde), car firstOrCreate retourne le cert existant MAIS la journalisation
        //         s'exécutait inconditionnellement. La garde wasRecentlyCreated est la même
        //         que celle déjà appliquée sur la notification (ligne ci-dessous).
        if ($certificate->wasRecentlyCreated) {
            // ActivityLog défensif — UNIQUEMENT à la première émission
            if (class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
                try {
                    activity('academy')
                        ->performedOn($certificate)
                        ->causedBy($user)
                        ->withProperties(['course_id' => $course->id, 'serial' => $certificate->serial])
                        ->log('academy.certificate.issued');
                } catch (\Throwable) {
                    // Silencieux
                }
            }

            // Événement défensif — UNIQUEMENT à la première émission
            try {
                event('academy.certificate.issued', [$user, $course, $certificate]);
            } catch (\Throwable) {
                // Silencieux
            }
        }

        // V5-c - Notification « cours complété » UNIQUEMENT à la première émission
        // (wasRecentlyCreated) : idempotent, jamais de doublon sur ré-appel. Gardée
        // par l'interrupteur maître + préférence. Défensif : ne casse jamais l'émission.
        if ($certificate->wasRecentlyCreated) {
            try {
                app(\Modules\Academy\Services\AcademyNotificationService::class)
                    ->courseCompleted($user, $course, $certificate);
            } catch (\Throwable) {
                // Silencieux
            }
        }

        return $certificate;
    }

    /**
     * Retourne le certificat existant pour user+course, ou null si absent.
     * Ne déclenche aucune émission.
     */
    public function issuedFor(User $user, Course $course): ?CertificateIssued
    {
        return CertificateIssued::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
    }
}
