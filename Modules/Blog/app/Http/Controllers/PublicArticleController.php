<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Tag;
use Modules\Blog\States\PublishedArticleState;

class PublicArticleController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->get('category');
        $currentCategory = $categorySlug ? Category::where('slug->'.app()->getLocale(), $categorySlug)->first() : null;
        $tagFilter = $request->get('tag');
        $currentTag = $tagFilter;

        $query = Article::published()->with(['user', 'blogCategory'])->latest('published_at');

        if ($currentCategory) {
            $query->where('category_id', $currentCategory->id);
        }

        $query->when($tagFilter, fn ($q) => $q->whereHas('tagsRelation', fn ($t) => $t->where('slug', $tagFilter)));

        $articles = $query->paginate(9)->withQueryString();

        $categories = Category::where('is_active', true)
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderByDesc('articles_count')
            ->get();

        $recentArticles = Article::published()->with(['user', 'blogCategory'])->latest('published_at')->take(5)->get();

        $popularTags = Tag::whereHas('articles', fn ($q) => $q->published())
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderByDesc('articles_count')
            ->take(10)
            ->get();

        return view('blog::public.index', compact(
            'articles', 'categories', 'recentArticles', 'currentCategory', 'popularTags', 'currentTag'
        ));
    }

    public function show(Article $article): View
    {
        if (! $article->status->equals(PublishedArticleState::class)) {
            abort(404);
        }

        $article->load(['blogCategory', 'user', 'tagsRelation']);

        $comments = $article->comments()
            ->approved()
            ->whereNull('parent_id')
            ->with(['author', 'replies.author'])
            ->latest()
            ->get();

        $relatedArticles = Article::published()
            ->with(['user', 'blogCategory'])
            ->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        $recentArticles = Article::published()
            ->with(['user', 'blogCategory'])
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(5)
            ->get();

        return view('blog::public.show', compact(
            'article', 'comments', 'relatedArticles', 'recentArticles'
        ));
    }
}
