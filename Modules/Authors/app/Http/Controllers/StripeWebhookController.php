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
