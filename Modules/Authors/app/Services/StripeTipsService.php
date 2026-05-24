<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorProfile;

final class StripeTipsService
{
    private mixed $stripe;

    public function __construct()
    {
        if (class_exists(\Stripe\StripeClient::class) && config('services.stripe.secret')) {
            $this->stripe = new \Stripe\StripeClient((string) config('services.stripe.secret'));
        } else {
            $this->stripe = null;
        }
    }

    public function createConnectExpressAccountLink(AuthorProfile $author): string
    {
        if (! $this->stripe) {
            throw new Exception('Stripe non configuré');
        }

        try {
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'CA',
                'email' => $author->user?->email,
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
            ]);

            $author->forceFill(['stripe_connect_account_id' => $account->id])->save();

            $accountLink = $this->stripe->accountLinks->create([
                'account' => $account->id,
                'refresh_url' => url('/auteur/stripe/refresh/'.$author->id),
                'return_url' => url('/auteur/stripe/return/'.$author->id),
                'type' => 'account_onboarding',
            ]);

            return (string) $accountLink->url;
        } catch (\Throwable $e) {
            Log::warning('Stripe Connect Express creation failed: '.$e->getMessage());
            throw new Exception('Impossible de créer le compte Stripe Connect');
        }
    }

    public function getCommissionRate(string $tier): float
    {
        return match ($tier) {
            'free' => 0.10,
            'education' => 0.05,
            'premium', 'premium_manual' => 0.00,
            default => 0.10,
        };
    }

    public function getTransparencyTable(string $tier): array
    {
        $commissionRate = $this->getCommissionRate($tier);
        $examples = [];

        foreach ([2, 5, 10, 20, 50] as $donAmount) {
            $amountCents = $donAmount * 100;
            $stripeFeesCents = ($amountCents * 0.029) + 30;
            $netAfterStripe = $amountCents - $stripeFeesCents;
            $memoraCommissionCents = $netAfterStripe * $commissionRate;
            $receivedCents = $netAfterStripe - $memoraCommissionCents;

            $examples[] = [
                'don' => $donAmount,
                'recoit' => round($receivedCents / 100, 2),
            ];
        }

        return [
            'tier' => $tier,
            'commission_rate_percent' => $commissionRate * 100,
            'examples' => $examples,
        ];
    }
}
