<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\SaaS\Models\Referral;

class ReferralService
{
    public function generateCode(User $user): Referral
    {
        return Referral::create([
            'referrer_id' => $user->id,
            'code' => Referral::generateCode(),
            'status' => Referral::STATUS_PENDING,
        ]);
    }

    public function track(string $code, User $referred): ?Referral
    {
        $referral = $this->getReferralByCode($code);

        if (! $referral || $referral->status !== Referral::STATUS_PENDING) {
            return null;
        }

        $referral->update([
            'referred_id' => $referred->id,
            'status' => Referral::STATUS_CONVERTED,
            'converted_at' => now(),
        ]);

        return $referral;
    }

    public function reward(Referral $referral, string $type = 'credit', ?float $value = null): Referral
    {
        $referral->update([
            'status' => Referral::STATUS_REWARDED,
            'reward_type' => $type,
            'reward_value' => $value,
            'rewarded_at' => now(),
        ]);

        return $referral->fresh();
    }

    /**
     * @return array{total_referrals: int, converted: int, rewarded: int, pending: int, expired: int}
     */
    public function getStats(User $user): array
    {
        $referrals = Referral::forReferrer($user->id)->get();

        return [
            'total_referrals' => $referrals->count(),
            'converted' => $referrals->where('status', Referral::STATUS_CONVERTED)->count(),
            'rewarded' => $referrals->where('status', Referral::STATUS_REWARDED)->count(),
            'pending' => $referrals->where('status', Referral::STATUS_PENDING)->count(),
            'expired' => $referrals->where('status', Referral::STATUS_EXPIRED)->count(),
        ];
    }

    public function getReferralByCode(string $code): ?Referral
    {
        return Referral::where('code', $code)->first();
    }

    public function getUserReferrals(User $user): Collection
    {
        return Referral::with('referred')
            ->forReferrer($user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function expireOldReferrals(int $days = 30): int
    {
        return Referral::where('status', Referral::STATUS_PENDING)
            ->where('created_at', '<', now()->subDays($days))
            ->update(['status' => Referral::STATUS_EXPIRED]);
    }
}
