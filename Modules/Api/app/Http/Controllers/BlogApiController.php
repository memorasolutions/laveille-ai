<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Api\Http\Resources\ArticleResource;
use Modules\Api\Http\Resources\CommentResource;
use Modules\Blog\Models\Article;
use Modules\Settings\Facades\Settings;

/**
 * @group Blog
 *
 * Public endpoints for browsing published articles, searching and listing categories.
 */
final class BlogApiController extends BaseApiController
{
    /**
     * Return a paginated list of published articles, optionally filtered by category.
     *
     * @unauthenticated
     */
    public function index(Request $request): JsonResponse
    {
        $articles = Article::published()
            ->with(['user', 'blogCategory'])
            ->withCount('comments')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->paginate((int) Settings::get('api.blog_articles_per_page', 15));

        return $this->respondSuccess(ArticleResource::collection($articles));
    }

    /**
     * Return a single published article and its approved comments by slug.
     *
     * @unauthenticated
     */
    public function show(string $slug): JsonResponse
    {
        $article = Article::with(['user', 'blogCategory'])
            ->where('slug->'.app()->getLocale(), $slug)
            ->published()
            ->first();

        if (! $article) {
            return $this->respondNotFound();
        }

        return $this->respondSuccess([
            'article' => new ArticleResource($article),
            'comments' => CommentResource::collection($article->comments()->approved()->get()),
        ]);
    }

    /**
     * Full-text search across published article titles and content.
     *
     * @unauthenticated
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2']]);

        $locale = app()->getLocale();
        $q = '%'.$request->q.'%';

        $articles = Article::published()
            ->with(['user', 'blogCategory'])
            ->where("title->{$locale}", 'LIKE', $q)
            ->orWhere(fn ($query) => $query->where("content->{$locale}", 'LIKE', $q)->published())
            ->paginate((int) Settings::get('api.blog_articles_per_page', 15));

        return $this->respondSuccess(ArticleResource::collection($articles));
    }

    /**
     * Return a deduplicated list of all categories used by published articles.
     *
     * @unauthenticated
     */
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('api.blog.categories', 3600, fn () => Article::published()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->values());

        return $this->respondSuccess($categories);
    }
}
