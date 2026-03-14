<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Services;

use App\Models\User;
use Laravel\Cashier\Subscription;

final class SubscriptionService
{
    public function cancel(User $user): ?string
    {
        $subscription = $user->subscription('default');

        if (! $subscription) {
            return null;
        }

        $subscription->cancel();

        return $subscription->ends_at?->toDateString();
    }

    public function resume(User $user): bool
    {
        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->onGracePeriod()) {
            return false;
        }

        $subscription->resume();

        return true;
    }

    public function swap(User $user, string $stripePriceId): ?Subscription
    {
        return $user->subscription('default')?->swap($stripePriceId);
    }

    /** @return array{plan: string, status: string, trial_ends_at: ?string, renews_at: ?string, on_grace_period: bool, subscribed: bool} */
    public function getStatus(User $user): array
    {
        $subscription = $user->subscription('default');

        if (! $subscription) {
            return [
                'plan' => '',
                'status' => 'inactive',
                'trial_ends_at' => null,
                'renews_at' => null,
                'on_grace_period' => false,
                'subscribed' => false,
            ];
        }

        return [
            'plan' => $subscription->stripe_price ?? '',
            'status' => $subscription->stripe_status ?? 'inactive',
            'trial_ends_at' => $subscription->trial_ends_at?->toDateString(),
            'renews_at' => $subscription->created_at?->toDateString(),
            'on_grace_period' => $subscription->onGracePeriod(),
            'subscribed' => $this->isSubscribed($user),
        ];
    }

    /** @return array<int, \Laravel\Cashier\Invoice> */
    public function getInvoices(User $user): array
    {
        if (! $user->hasStripeId()) {
            return [];
        }

        return $user->invoices()->toArray();
    }

    public function isSubscribed(User $user): bool
    {
        return $user->subscribed('default');
    }

    public function onTrial(User $user): bool
    {
        return $user->subscription('default')?->onTrial() ?? false;
    }

    public function onGracePeriod(User $user): bool
    {
        return $user->subscription('default')?->onGracePeriod() ?? false;
    }
}
