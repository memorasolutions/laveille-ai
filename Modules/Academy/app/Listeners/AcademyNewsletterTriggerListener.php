<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Academy\Events\AcademyCertificateIssued;
use Modules\Academy\Events\AcademyEnrollmentCreated;
use Modules\Academy\Events\AcademyItemCompleted;
use Throwable;

/**
 * Déclenche les automations Newsletter sur les événements Academy.
 * ENTIÈREMENT DÉFENSIF : no-op si le module Newsletter est absent ou inactif.
 * Idempotent : WorkflowEngine::enroll() utilise firstOrCreate en interne.
 */
final class AcademyNewsletterTriggerListener
{
    public function handleEnrollment(AcademyEnrollmentCreated $event): void
    {
        $this->triggerNewsletterWorkflow(
            $event->user,
            'academy_enrolled',
            ['course_id' => $event->course->id],
        );
    }

    public function handleCompletion(AcademyItemCompleted $event): void
    {
        $this->triggerNewsletterWorkflow(
            $event->user,
            'academy_item_completed',
            [
                'course_id'     => $event->course->id,
                'completion_id' => $event->completion->id,
            ],
        );
    }

    public function handleCertificate(AcademyCertificateIssued $event): void
    {
        $this->triggerNewsletterWorkflow(
            $event->user,
            'academy_certificate_issued',
            [
                'course_id'      => $event->course->id,
                'certificate_id' => $event->certificate->id,
            ],
        );
    }

    private function triggerNewsletterWorkflow(User $user, string $triggerType, array $metadata = []): void
    {
        try {
            // Garde 1 : module Newsletter activé
            if (! function_exists('module_enabled') || ! module_enabled('Newsletter')) {
                return;
            }

            // Garde 2 : classes Newsletter disponibles
            if (! class_exists('\Modules\Newsletter\Models\Subscriber')
                || ! class_exists('\Modules\Newsletter\Models\EmailWorkflow')
                || ! class_exists('\Modules\Newsletter\Services\WorkflowEngine')
            ) {
                return;
            }

            // Trouver l'abonné actif correspondant à l'utilisateur
            /** @var \Modules\Newsletter\Models\Subscriber|null $subscriber */
            $subscriber = \Modules\Newsletter\Models\Subscriber::where('email', $user->email)->first();

            if ($subscriber === null || ! method_exists($subscriber, 'isActive') || ! $subscriber->isActive()) {
                return;
            }

            // Trouver les workflows actifs du type demandé
            /** @var \Illuminate\Database\Eloquent\Collection $workflows */
            $workflows = \Modules\Newsletter\Models\EmailWorkflow::active()
                ->where('trigger_type', $triggerType)
                ->get();

            if ($workflows->isEmpty()) {
                return;
            }

            $engine = new \Modules\Newsletter\Services\WorkflowEngine();

            foreach ($workflows as $workflow) {
                // enroll() est idempotent (firstOrCreate) — pas besoin de vérification supplémentaire
                $engine->enroll($workflow, $subscriber);
            }
        } catch (Throwable $e) {
            // Ne jamais laisser l'automation Newsletter bloquer un flux Academy
            Log::error('AcademyNewsletterTriggerListener: échec du déclenchement workflow', [
                'user_id'      => $user->id,
                'trigger_type' => $triggerType,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
