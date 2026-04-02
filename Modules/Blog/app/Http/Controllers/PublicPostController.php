<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Settings\Facades\Settings;
use Nwidart\Modules\Facades\Module;

class PublicPostController extends Controller
{
    public function __construct()
    {
        abort_unless(
            Module::has('FrontTheme') && Module::find('FrontTheme')?->isEnabled(),
            404,
            'FrontTheme module is not available.'
        );
    }

    public function index(Request $request): View
    {
        $query = Article::query()
            ->published()
            ->with(['user', 'blogCategory', 'tagsRelation']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tag')) {
            $tagSlug = $request->input('tag');
            $query->whereHas('tagsRelation', function (Builder $q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        $currentCategory = null;
        if ($request->filled('category')) {
            $categorySlug = $request->input('category');
            $locale = app()->getLocale();
            $currentCategory = Category::where("slug->{$locale}", $categorySlug)
                ->orWhere('slug', $categorySlug)
                ->first();
            if ($currentCategory) {
                $query->where('category_id', $currentCategory->id);
            }
        }

        $articles = $query->latest('published_at')->paginate((int) Settings::get('blog.articles_per_page', 10));

        $categories = Category::withCount(['articles as published_articles_count' => function (Builder $q) {
            $q->published();
        }])
            ->having('published_articles_count', '>', 0)
            ->orderByDesc('published_articles_count')
            ->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('fronttheme::blog._article-cards', compact('articles'))->render(),
                'hasMore' => $articles->hasMorePages(),
                'nextPage' => $articles->currentPage() + 1,
            ]);
        }

        return view('fronttheme::blog.index', compact('articles', 'categories', 'currentCategory'));
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        $article = Article::query()
            ->published()
            ->where(function (Builder $q) use ($slug, $locale) {
                $q->where("slug->{$locale}", $slug)
                    ->orWhere('slug', $slug);
            })
            ->with(['user', 'blogCategory', 'tagsRelation'])
            ->firstOrFail();

        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->when($article->blog_category_id, fn ($q) => $q->where('blog_category_id', $article->blog_category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('fronttheme::blog.show', compact('article', 'relatedArticles'));
    }

    public function category(string $slug): View
    {
        $locale = app()->getLocale();

        $category = Category::where("slug->{$locale}", $slug)
            ->orWhere('slug', $slug)
            ->firstOrFail();

        $articles = Article::query()
            ->published()
            ->where('category_id', $category->id)
            ->with(['user', 'tagsRelation'])
            ->latest('published_at')
            ->paginate((int) Settings::get('blog.articles_per_page', 10));

        return view('fronttheme::blog.category', compact('category', 'articles'));
    }
}
