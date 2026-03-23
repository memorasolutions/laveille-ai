<?php

declare(strict_types=1);

namespace Modules\Community\Traits;

use Modules\Community\Models\Comment;

trait HasComments
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function approvedComments()
    {
        return $this->comments()->approved();
    }

    public function commentsCount(): int
    {
        return $this->comments()->count();
    }
}
