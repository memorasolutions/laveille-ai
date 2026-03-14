<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Services;

use Illuminate\Support\Str;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Changelog;
use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Models\Vote;
use Modules\Roadmap\Notifications\IdeaStatusChanged;

class IdeaService
{
    public function create(Board $board, array $data, int $userId): Idea
    {
        $data['board_id'] = $board->id;
        $data['user_id'] = $userId;
        $data['slug'] = Str::slug($data['title']).'-'.uniqid();
        $data['status'] = 'under_review';

        return Idea::create($data);
    }

    public function updateStatus(Idea $idea, string $status): Idea
    {
        $oldStatus = $idea->status->value;

        $idea->update(['status' => $status]);

        Changelog::create([
            'idea_id' => $idea->id,
            'user_id' => auth()->id(),
            'field' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $status,
        ]);

        if ($idea->user && $oldStatus !== $status) {
            $idea->user->notify(new IdeaStatusChanged($idea->fresh(), $oldStatus, $status));
        }

        return $idea->fresh();
    }

    public function merge(Idea $source, Idea $target): void
    {
        Vote::where('idea_id', $source->id)
            ->whereNotIn('user_id', $target->votes()->pluck('user_id'))
            ->update(['idea_id' => $target->id]);

        Vote::where('idea_id', $source->id)->delete();

        $target->update(['vote_count' => $target->votes()->count()]);

        $source->update([
            'merged_into_id' => $target->id,
            'status' => 'declined',
        ]);

        Changelog::create([
            'idea_id' => $source->id,
            'user_id' => auth()->id(),
            'field' => 'merged',
            'old_value' => null,
            'new_value' => (string) $target->id,
            'note' => 'Merged into: '.$target->title,
        ]);
    }
}
