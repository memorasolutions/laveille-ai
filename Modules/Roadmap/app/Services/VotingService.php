<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Services;

use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Models\Vote;

class VotingService
{
    public function toggle(Idea $idea, int $userId): bool
    {
        $vote = Vote::where('idea_id', $idea->id)
            ->where('user_id', $userId)
            ->first();

        if ($vote) {
            $vote->delete();
            $idea->decrement('vote_count');

            return false;
        }

        Vote::create([
            'idea_id' => $idea->id,
            'user_id' => $userId,
        ]);

        $idea->increment('vote_count');

        return true;
    }

    public function hasVoted(Idea $idea, int $userId): bool
    {
        return Vote::where('idea_id', $idea->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
