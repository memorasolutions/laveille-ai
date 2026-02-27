<?php

declare(strict_types=1);

namespace Modules\Blog\Policies;

use App\Models\User;
use Modules\Blog\Models\Comment;

class CommentPolicy
{
    public function create(?User $user): bool
    {
        return true;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->can('manage_comments') || $user->id === $comment->user_id;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->can('manage_comments');
    }
}
