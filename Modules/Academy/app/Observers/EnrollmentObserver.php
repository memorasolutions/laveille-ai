<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TUTEUR IA — Calcule et FIGE la fenêtre d'accès au moment de l'inscription,
 * quel que soit le chemin de création (gratuit EnrollmentService, achat
 * StripeWebhookListener, seeder de démo...) — SOURCE UNIQUE (DRY), un seul
 * point d'accroche plutôt qu'un appel dupliqué dans chaque service.
 *
 * NO-OP tant que le drapeau academy.ai_tutor_access_control_enabled est faux
 * (défaut) : voir TutorAccessService::calculateGrantFor(). ZÉRO exception
 * propagée : un échec de calcul ne doit jamais faire échouer une inscription.
 */

declare(strict_types=1);

namespace Modules\Academy\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\TutorAccessService;
use Throwable;

class EnrollmentObserver
{
    public function created(Enrollment $enrollment): void
    {
        try {
            $course = $enrollment->course;
            $user   = $enrollment->user;

            if ($course === null || $user === null) {
                return;
            }

            app(TutorAccessService::class)->calculateGrantFor($user, $course);
        } catch (Throwable $e) {
            Log::warning('[Academy] EnrollmentObserver : calcul du grant Tuteur IA ignoré.', [
                'enrollment_id' => $enrollment->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
