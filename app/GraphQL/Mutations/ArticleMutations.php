<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace App\GraphQL\Mutations;

use Illuminate\Support\Str;
use Modules\Blog\Models\Article;

final class ArticleMutations
{
    public function createArticle(mixed $root, array $args): Article
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $locale = app()->getLocale();

        $article = new Article;
        $article->user_id = $user->id;
        $article->status = $args['status'];
        $article->category_id = $args['category_id'] ?? null;
        $article->published_at = $args['status'] === 'published' ? now() : null;
        $article->setTranslation('title', $locale, $args['title']);
        $article->setTranslation('slug', $locale, Str::slug($args['title']));
        $article->setTranslation('content', $locale, $args['content']);

        if (isset($args['excerpt'])) {
            $article->setTranslation('excerpt', $locale, $args['excerpt']);
        }

        $article->save();

        return $article->fresh();
    }

    public function updateArticle(mixed $root, array $args): Article
    {
        /** @var Article $article */
        $article = Article::findOrFail($args['id']);
        $locale = app()->getLocale();

        if (isset($args['status'])) {
            $article->status = $args['status'];
            if ($args['status'] === 'published' && $article->published_at === null) {
                $article->published_at = now();
            }
        }

        if (isset($args['title'])) {
            $article->setTranslation('title', $locale, $args['title']);
            $article->setTranslation('slug', $locale, Str::slug($args['title']));
        }

        if (isset($args['content'])) {
            $article->setTranslation('content', $locale, $args['content']);
        }

        if (isset($args['excerpt'])) {
            $article->setTranslation('excerpt', $locale, $args['excerpt']);
        }

        $article->save();

        return $article->fresh();
    }

    public function deleteArticle(mixed $root, array $args): Article
    {
        /** @var Article $article */
        $article = Article::withoutGlobalScopes()->findOrFail($args['id']);

        $this->authorize('delete', $article);

        $article->delete();

        return $article;
    }

    private function authorize(string $ability, mixed $model): void
    {
        if (! auth()->user()?->can($ability, $model)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('This action is unauthorized.');
        }
    }
}
