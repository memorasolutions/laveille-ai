<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorProfile;
use RuntimeException;

final class AuthorTipsService
{
    public function createCheckout(AuthorProfile $author, int $amountCents = 500, string $currency = 'cad'): string
    {
        $userId = $author->user_id;

        if ($userId === null) {
            throw new RuntimeException('Author has no user account for tips');
        }

        $user = User::findOrFail($userId);

        $session = $user->checkoutCharge(
            $amountCents,
            "Tip pour {$author->display_name}",
            1,
            [
                'mode' => 'payment',
                'success_url' => url("/@{$author->slug}").'?tip=success',
                'cancel_url' => url("/@{$author->slug}").'?tip=cancelled',
                'metadata' => [
                    'author_profile_id' => (string) $author->id,
                    'author_slug' => $author->slug,
                    'tip_type' => 'one-time',
                ],
            ]
        );

        Log::channel('daily')->info('tips.checkout.created', [
            'author_profile_id' => $author->id,
            'amount_cents' => $amountCents,
        ]);

        return $session->url;
    }

    public function isEnabled(): bool
    {
        return ! empty(config('cashier.secret'));
    }
}
