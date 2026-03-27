<?php

declare(strict_types=1);

namespace Modules\Voting\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Voting\Models\Vote;

trait HasCommunityVotes
{
    public function communityVotes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function toggleVote(User $user): bool
    {
        $existing = $this->communityVotes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        $this->communityVotes()->create(['user_id' => $user->id]);

        return true;
    }

    public function communityVoteCount(): int
    {
        return $this->communityVotes()->count();
    }

    public function hasVoted(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->communityVotes()->where('user_id', $user->id)->exists();
    }

    public function getBadgeTier(): string
    {
        $count = $this->communityVoteCount();

        if ($count >= config('voting.thresholds.favorite', 10)) {
            return 'favorite';
        }
        if ($count >= config('voting.thresholds.approved', 5)) {
            return 'approved';
        }
        if ($count >= config('voting.thresholds.noticed', 2)) {
            return 'noticed';
        }

        return 'none';
    }
}
