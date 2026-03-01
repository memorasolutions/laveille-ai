<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Policies;

use App\Models\User;
use Modules\Blog\Models\Article;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_articles');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->can('manage_articles') || $user->id === $article->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_articles');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('manage_articles') || $user->id === $article->user_id;
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('manage_articles') || $user->id === $article->user_id;
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->can('manage_articles');
    }
}
