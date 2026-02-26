<?php

declare(strict_types=1);

namespace Modules\Blog\Policies;

use App\Models\User;
use Modules\Blog\Models\Article;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, Article $article): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->id === $article->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, Article $article): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->id === $article->user_id;
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->id === $article->user_id;
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
