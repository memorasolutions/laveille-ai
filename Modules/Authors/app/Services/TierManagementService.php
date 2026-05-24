<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Authors\Models\AuthorActivityLog;
use Modules\Authors\Models\AuthorProfile;

final class TierManagementService
{
    public function setTier(int $authorProfileId, string $tier, int $adminUserId, ?string $reason = null, ?\DateTimeInterface $expiresAt = null): bool
    {
        if (! in_array($tier, ['free', 'education', 'premium', 'premium_manual'], true)) {
            throw new \InvalidArgumentException("Invalid tier: {$tier}");
        }

        $profile = AuthorProfile::findOrFail($authorProfileId);
        $oldTier = $profile->tier;
        if ($oldTier === $tier) {
            return true;
        }

        $profile->tier = $tier;
        $profile->tier_expires_at = $expiresAt;
        $profile->save();

        AuthorActivityLog::create([
            'author_profile_id' => $authorProfileId,
            'event_type' => 'tier_changed',
            'event_meta' => [
                'tier_old' => $oldTier,
                'tier_new' => $tier,
                'admin_user_id' => $adminUserId,
                'reason' => $reason,
                'expires_at' => $expiresAt ? $expiresAt->format('c') : null,
            ],
        ]);

        return true;
    }

    public function approveEducation(int $authorProfileId, int $adminUserId, ?string $institutionUrl = null): bool
    {
        $profile = AuthorProfile::findOrFail($authorProfileId);
        $now = Carbon::now();

        $profile->tier = 'education';
        $profile->education_approved_at = $now;
        $profile->education_approved_by = $adminUserId;
        $profile->tier_expires_at = $now->copy()->addYear();
        $profile->save();

        AuthorActivityLog::create([
            'author_profile_id' => $authorProfileId,
            'event_type' => 'tier_changed',
            'event_meta' => [
                'sub_type' => 'education_approved',
                'admin_user_id' => $adminUserId,
                'institution_url' => $institutionUrl,
                'expires_at' => $profile->tier_expires_at->format('c'),
            ],
        ]);

        return true;
    }

    public function revokeEducation(int $authorProfileId, int $adminUserId, ?string $reason = null): bool
    {
        $profile = AuthorProfile::findOrFail($authorProfileId);
        if ($profile->tier !== 'education') {
            return true;
        }

        $profile->tier = 'free';
        $profile->tier_expires_at = null;
        $profile->education_approved_at = null;
        $profile->education_approved_by = null;
        $profile->save();

        AuthorActivityLog::create([
            'author_profile_id' => $authorProfileId,
            'event_type' => 'tier_changed',
            'event_meta' => ['sub_type' => 'education_revoked', 'admin_user_id' => $adminUserId, 'reason' => $reason],
        ]);

        return true;
    }

    public function expireExpiredTiers(): int
    {
        $now = Carbon::now();
        $expired = AuthorProfile::whereIn('tier', ['education', 'premium_manual'])
            ->where('tier_expires_at', '<', $now)
            ->get();

        $count = 0;
        foreach ($expired as $profile) {
            $oldTier = $profile->tier;
            $profile->tier = 'free';
            $profile->tier_expires_at = null;
            if ($oldTier === 'education') {
                $profile->education_approved_at = null;
                $profile->education_approved_by = null;
            }
            $profile->save();

            AuthorActivityLog::create([
                'author_profile_id' => $profile->id,
                'event_type' => 'tier_changed',
                'event_meta' => ['sub_type' => 'tier_expired_auto', 'tier_old' => $oldTier, 'tier_new' => 'free'],
            ]);

            $count++;
        }

        return $count;
    }

    public function getAuditLog(int $authorProfileId, int $limit = 50): Collection
    {
        return AuthorActivityLog::where('author_profile_id', $authorProfileId)
            ->where('event_type', 'tier_changed')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
