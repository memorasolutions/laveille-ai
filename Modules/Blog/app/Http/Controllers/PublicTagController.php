<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Tag;

class PublicTagController extends Controller
{
    public function show(Tag $tag): View
    {
        $articles = $tag->articles()
            ->published()
            ->with(['user', 'blogCategory'])
            ->latest('published_at')
            ->paginate(9);

        $categories = Category::where('is_active', true)
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderByDesc('articles_count')
            ->get();

        $recentArticles = Article::published()->latest('published_at')->take(5)->get();

        $popularTags = Tag::whereHas('articles', fn ($q) => $q->published())
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderByDesc('articles_count')
            ->take(10)
            ->get();

        return view('blog::public.tag', compact('tag', 'articles', 'categories', 'recentArticles', 'popularTags'));
    }
}
