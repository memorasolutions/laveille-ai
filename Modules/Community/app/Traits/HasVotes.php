<?php

declare(strict_types=1);

namespace Modules\Community\Traits;

use Modules\Community\Models\Vote;

trait HasVotes
{
    public function votes()
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function voteScore(): int
    {
        return (int) $this->votes()->sum('value');
    }
}
