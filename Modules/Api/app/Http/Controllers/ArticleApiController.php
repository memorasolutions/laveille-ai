<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Api\Http\Requests\StoreArticleRequest;
use Modules\Api\Http\Requests\UpdateArticleRequest;
use Modules\Api\Http\Resources\ArticleResource;
use Modules\Blog\Models\Article;

/**
 * @group Articles
 *
 * Endpoints for creating, updating and deleting blog articles (authenticated editors/admins).
 */
class ArticleApiController extends BaseApiController
{
    /**
     * Create a new blog article (draft or published).
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = $request->user()->id;

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $article = Article::create($validated);

        return $this->respondCreated(new ArticleResource($article->load(['user', 'blogCategory'])));
    }

    /**
     * Update an existing article's content or status.
     */
    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $this->authorize('update', $article);

        $validated = $request->validated();

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $article->update($validated);

        return $this->respondSuccess(new ArticleResource($article->fresh()->load(['user', 'blogCategory'])));
    }

    /**
     * Delete an article permanently.
     */
    public function destroy(Article $article): JsonResponse
    {
        $this->authorize('delete', $article);

        $article->delete();

        return $this->respondSuccess(message: 'Article supprimé.');
    }
}
