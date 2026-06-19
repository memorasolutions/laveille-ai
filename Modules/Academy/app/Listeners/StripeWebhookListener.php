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
use Laravel\Cashier\Events\WebhookHandled;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Throwable;

/**
 * Écoute l'event Cashier WebhookHandled et inscrit l'utilisateur
 * quand un paiement de cours est confirmé (checkout.session.completed).
 *
 * Aucune nouvelle route webhook n'est ajoutée : réutilise POST /stripe/webhook (cashier.webhook).
 */
class StripeWebhookListener
{
    /**
     * @param  \Laravel\Cashier\Events\WebhookHandled  $event
     * @return void
     */
    public function handle(WebhookHandled $event): void
    {
        try {
            // Seulement checkout.session.completed
            if (($event->payload['type'] ?? '') !== 'checkout.session.completed') {
                return;
            }

            $object   = $event->payload['data']['object'] ?? [];
            $metadata = $object['metadata'] ?? [];

            $courseId = $metadata['course_id'] ?? null;
            $userId   = $metadata['user_id'] ?? null;

            // Défensif : si la métadonnée Academy est absente, c'est un autre paiement → ignorer
            if ($courseId === null || $userId === null) {
                return;
            }

            $course = Course::find((int) $courseId);
            if ($course === null) {
                return;
            }

            $user = User::find((int) $userId);
            if ($user === null) {
                return;
            }

            $cashierId   = $object['payment_intent'] ?? null;
            $amountCents = isset($object['amount_total']) ? (int) $object['amount_total'] : null;
            $currency    = $object['currency'] ?? null;

            // Idempotent : firstOrCreate sur (user_id, course_id) — contrainte unique en DB
            Enrollment::firstOrCreate(
                [
                    'user_id'   => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'status'       => 'active',
                    'source'       => 'purchase',
                    'cashier_id'   => $cashierId,
                    'amount_cents' => $amountCents,
                    'currency'     => $currency,
                    'enrolled_at'  => now(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('Academy StripeWebhookListener error', [
                'err'  => $e->getMessage(),
                'type' => $event->payload['type'] ?? 'unknown',
            ]);
            // Ne jamais relancer l'exception : Cashier a déjà envoyé le 200 OK à Stripe
        }
    }
}
