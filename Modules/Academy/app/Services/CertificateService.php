<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
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

        // Progression persistée (pour les heures + la note du certificat). Peut être
        // absente si la complétion est atteinte par un critère hors items requis : on
        // reste défensif (final_score retombe sur 100).
        $progress = Progress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        // Idempotence : retourner le certificat existant
        $existing = CertificateIssued::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        // Génération des champs uniques
        $serial           = 'ACAD-' . strtoupper(substr(md5(uniqid((string) $user->id . (string) $course->id, true)), 0, 12));
        $verificationHash = hash('sha256', $user->id . $course->id . uniqid('', true) . config('app.key', ''));
        $publicUrlSlug    = 'cert-' . substr($verificationHash, 0, 16) . '-' . time();
        $hoursEarned      = (int) ceil(($course->duration_minutes ?? 0) / 60);
        $finalScore       = ($progress !== null && $progress->required_total > 0)
            ? (int) round($progress->required_completed / $progress->required_total * 100)
            : 100;

        $certificate = CertificateIssued::create([
            'user_id'           => $user->id,
            'course_id'         => $course->id,
            'serial'            => $serial,
            'verification_hash' => $verificationHash,
            'public_url_slug'   => $publicUrlSlug,
            'issued_at'         => now(),
            'hours_earned'      => $hoursEarned,
            'final_score'       => $finalScore,
        ]);

        // ActivityLog défensif
        if (class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            try {
                activity('academy')
                    ->performedOn($certificate)
                    ->causedBy($user)
                    ->withProperties(['course_id' => $course->id, 'serial' => $serial])
                    ->log('academy.certificate.issued');
            } catch (\Throwable) {
                // Silencieux
            }
        }

        // Événement défensif
        try {
            event('academy.certificate.issued', [$user, $course, $certificate]);
        } catch (\Throwable) {
            // Silencieux
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
