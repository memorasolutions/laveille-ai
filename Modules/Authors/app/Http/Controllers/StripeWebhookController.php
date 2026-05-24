<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorProfile;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends \Laravel\Cashier\Http\Controllers\WebhookController
{
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);
        $this->syncTier($payload, 'premium');

        return $response;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        $status = $payload['data']['object']['status'] ?? 'inactive';
        $newTier = in_array($status, ['active', 'trialing'], true) ? 'premium' : 'free';
        $this->syncTier($payload, $newTier);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);
        $this->syncTier($payload, 'free');

        return $response;
    }

    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $response = method_exists(\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleCheckoutSessionCompleted')
            ? parent::handleCheckoutSessionCompleted($payload)
            : new \Symfony\Component\HttpFoundation\Response('Webhook handled', 200);
        $object = $payload['data']['object'] ?? [];
        $metadata = $object['metadata'] ?? [];
        $tipType = $metadata['tip_type'] ?? null;

        if ($tipType !== 'one-time') {
            return $response;
        }

        $authorProfileId = (int) ($metadata['author_profile_id'] ?? 0);
        if ($authorProfileId <= 0) {
            return $response;
        }

        $authorProfile = AuthorProfile::find($authorProfileId);
        if (! $authorProfile) {
            return $response;
        }

        $amountTotal = (int) ($object['amount_total'] ?? 0);
        $currency = (string) ($object['currency'] ?? 'cad');
        $tipperEmail = $object['customer_details']['email'] ?? null;

        Log::channel('daily')->info('tips.received', [
            'author_profile_id' => $authorProfile->id,
            'amount_total_cents' => $amountTotal,
            'currency' => $currency,
            'customer_email' => $tipperEmail,
        ]);

        if ($authorProfile->user_id && $authorProfile->user?->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($authorProfile->user->email)
                    ->queue(new \Modules\Authors\Mail\TipReceivedNotificationMail(
                        $authorProfile,
                        $amountTotal,
                        $currency,
                        $tipperEmail
                    ));
            } catch (\Throwable $e) {
                Log::channel('daily')->error('tips.notification.mail.failed', [
                    'author_profile_id' => $authorProfile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }

    private function syncTier(array $payload, string $tier): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;
        if (! $stripeId) {
            return;
        }

        $user = User::where('stripe_id', $stripeId)->first();
        if (! $user) {
            return;
        }

        $authorProfile = AuthorProfile::where('user_id', $user->id)->first();
        if ($authorProfile) {
            $authorProfile->update(['tier' => $tier]);
            Log::channel('daily')->info('stripe.sync_tier', [
                'user_id' => $user->id,
                'tier' => $tier,
                'author_profile_id' => $authorProfile->id,
            ]);
        }
    }
}
