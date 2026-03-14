<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Modules\SaaS\Notifications\PaymentFailedNotification;
use Modules\SaaS\Notifications\PaymentSucceededNotification;
use Modules\SaaS\Notifications\SubscriptionCancelledNotification;

class StripeWebhookController extends CashierWebhookController
{
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        Log::info('Stripe: subscription created', [
            'stripe_id' => $payload['data']['object']['id'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionCreated($payload);
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        Log::info('Stripe: subscription updated', [
            'stripe_id' => $payload['data']['object']['id'] ?? null,
            'status' => $payload['data']['object']['status'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        Log::info('Stripe: subscription deleted', [
            'stripe_id' => $payload['data']['object']['id'] ?? null,
        ]);

        $result = parent::handleCustomerSubscriptionDeleted($payload);

        $stripeId = $payload['data']['object']['customer'] ?? null;
        if ($stripeId) {
            $user = User::where('stripe_id', $stripeId)->first();
            $endsAt = $payload['data']['object']['current_period_end'] ?? null;
            $user?->notify(new SubscriptionCancelledNotification(
                $endsAt ? date('Y-m-d', (int) $endsAt) : null
            ));
        }

        return $result;
    }

    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        Log::info('Stripe: invoice payment succeeded', [
            'invoice_id' => $payload['data']['object']['id'] ?? null,
            'amount' => $payload['data']['object']['amount_paid'] ?? null,
        ]);

        $stripeId = $payload['data']['object']['customer'] ?? null;
        $invoiceId = $payload['data']['object']['id'] ?? 'unknown';
        $amount = $payload['data']['object']['amount_paid'] ?? 0;

        if ($stripeId) {
            $user = User::where('stripe_id', $stripeId)->first();
            $user?->notify(new PaymentSucceededNotification($invoiceId, (int) $amount));
        }

        return $this->successMethod();
    }

    protected function handleInvoicePaymentFailed(array $payload)
    {
        Log::warning('Stripe: invoice payment failed', [
            'invoice_id' => $payload['data']['object']['id'] ?? null,
            'customer' => $payload['data']['object']['customer'] ?? null,
        ]);

        $stripeId = $payload['data']['object']['customer'] ?? null;
        $invoiceId = $payload['data']['object']['id'] ?? 'unknown';

        if ($stripeId) {
            $user = User::where('stripe_id', $stripeId)->first();
            $user?->notify(new PaymentFailedNotification($invoiceId));
        }

        return $this->successMethod();
    }
}
