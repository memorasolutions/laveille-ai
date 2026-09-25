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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\States\PublishedArticleState;
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

    public function index(Request $request): View|JsonResponse
    {
        $locale = app()->getLocale();

        $query = Article::query()
            ->published()
            ->with(['user', 'blogCategory', 'tagsRelation']);

        if ($request->filled('search')) {
            $query->searchText($request->input('search'));
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
            $currentCategory = Category::where("slug->{$locale}", $categorySlug)
                ->orWhere('slug', $categorySlug)
                ->first();
            if ($currentCategory) {
                $query->where('category_id', $currentCategory->id);
            }
        }

        $articles = $query->latest('published_at')->paginate((int) Settings::get('blog.articles_per_page', 10));

        $search = $request->input('search');
        $tag = $request->input('tag');

        $categoriesQuery = Category::withCount(['articles as published_articles_count' => function (Builder $q) use ($search, $tag) {
            $q->published();
            if ($search) {
                $q->searchText($search);
            }
            if ($tag) {
                $q->whereHas('tagsRelation', fn (Builder $query) => $query->where('slug', $tag));
            }
        }]);

        if (!$search && !$tag) {
            $categoriesQuery->having('published_articles_count', '>', 0)
                ->orderByDesc('published_articles_count');
        } else {
            $categoriesQuery->orderBy("name->{$locale}");
        }

        $categories = $categoriesQuery->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('fronttheme::blog._article-cards', compact('articles'))->render(),
                'hasMore' => $articles->hasMorePages(),
                'nextPage' => $articles->currentPage() + 1,
            ]);
        }

        return view('fronttheme::blog.index', compact('articles', 'categories', 'currentCategory'));
    }

    public function show(string $slug): View|Response
    {
        $locale = app()->getLocale();

        $slugMatch = function (Builder $q) use ($slug, $locale) {
            $q->where("slug->{$locale}", $slug)
                ->orWhere('slug', $slug);
        };

        $article = Article::query()
            ->published()
            ->where($slugMatch)
            ->with(['user', 'blogCategory', 'tagsRelation'])
            ->first();

        if (! $article) {
            // Avant-première : un article au statut "published" dont published_at est dans le
            // futur est un article PLANIFIÉ (contrairement à un brouillon, qui reste 404). Bonne
            // pratique Google Search Central (2026-09-25) : l'adresse définitive sert une page
            // d'avant-première en 200/noindex jusqu'à la date prévue, jamais un 404 ni un 503 -
            // ni le contenu complet de l'article, qui ne doit JAMAIS transiter par cette page
            // (voir fronttheme::blog.upcoming, qui ne reçoit jamais $article->content).
            $upcoming = Article::query()
                ->whereState('status', PublishedArticleState::class)
                ->where('published_at', '>', now())
                ->where($slugMatch)
                ->with('blogCategory')
                ->first();

            abort_unless($upcoming, 404);

            return response()
                ->view('fronttheme::blog.upcoming', ['article' => $upcoming])
                ->header('X-Robots-Tag', 'noindex');
        }

        // Composants Blade réutilisables (ex. <x-fronttheme::text-generator .../>) embarqués dans le
        // contenu éditorial : Blade::render() DOIT s'exécuter ici, avant le début du rendu de la vue -
        // appelé au milieu d'un @section('content') actif, il corrompt la pile de sections partagée du
        // Factory et casse le @endsection principal (incident 2026-07-03).
        if (is_string($article->content) && str_contains($article->content, '<x-')) {
            try {
                $article->content = \Illuminate\Support\Facades\Blade::render($article->content);
            } catch (\Throwable $e) {
                \Log::warning('Blog show : Blade::render du contenu article a échoué', ['article_id' => $article->id, 'error' => $e->getMessage()]);
            }
        }

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
