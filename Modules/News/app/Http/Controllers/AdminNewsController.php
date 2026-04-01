<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\RssFetcherService;
use Modules\Settings\Facades\Settings;

class AdminNewsController extends Controller
{
    public function index(): View
    {
        $sources = NewsSource::withCount('articles')->paginate((int) Settings::get('news.admin_per_page', 20));

        return view('news::admin.sources.index', compact('sources'));
    }

    public function create(): View
    {
        return view('news::admin.sources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|unique:news_sources',
            'category' => 'nullable|string|max:255',
            'language' => ['nullable', 'string', Rule::in(['fr', 'en'])],
            'active' => 'boolean',
        ]);

        NewsSource::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'category' => $validated['category'] ?? null,
            'language' => $validated['language'] ?? 'fr',
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.news.sources.index')->with('success', __('Source RSS créée.'));
    }

    public function edit(NewsSource $source): View
    {
        return view('news::admin.sources.edit', compact('source'));
    }

    public function update(Request $request, NewsSource $source): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => ['required', 'url', Rule::unique('news_sources')->ignore($source->id)],
            'category' => 'nullable|string|max:255',
            'language' => ['nullable', 'string', Rule::in(['fr', 'en'])],
            'active' => 'boolean',
        ]);

        $source->update([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'category' => $validated['category'] ?? null,
            'language' => $validated['language'] ?? 'fr',
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.news.sources.index')->with('success', __('Source RSS mise à jour.'));
    }

    public function toggleActive(NewsSource $source): RedirectResponse
    {
        $source->update(['active' => ! $source->active]);

        return back()->with('success', __('Statut modifié.'));
    }

    public function destroy(NewsSource $source): RedirectResponse
    {
        $source->delete();

        return redirect()->route('admin.news.sources.index')->with('success', __('Source supprimée.'));
    }

    public function fetchNow(NewsSource $source, RssFetcherService $fetcher): RedirectResponse
    {
        $count = $fetcher->fetchSource($source);

        return back()->with('success', __(':count articles récupérés pour :name.', ['count' => $count, 'name' => $source->name]));
    }

    // ── Articles ──

    public function articles(Request $request): View
    {
        $query = NewsArticle::with('source')->latest('pub_date');

        // Filtres
        if ($request->filled('status')) {
            if ($request->status === 'published') $query->where('is_published', true);
            elseif ($request->status === 'filtered') $query->where('is_published', false);
        }
        if ($request->filled('category')) {
            $query->where('category_tag', $request->category);
        }
        if ($request->filled('feed')) {
            $query->where('feed_type', $request->feed);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $articles = $query->paginate(30)->appends($request->all());

        $categories = NewsArticle::whereNotNull('category_tag')
            ->distinct()->pluck('category_tag')->sort();

        $stats = [
            'total' => NewsArticle::count(),
            'published' => NewsArticle::where('is_published', true)->count(),
            'filtered' => NewsArticle::where('is_published', false)->count(),
            'today' => NewsArticle::whereDate('created_at', today())->where('is_published', true)->count(),
        ];

        return view('news::admin.articles.index', compact('articles', 'categories', 'stats'));
    }

    public function toggleArticle(NewsArticle $article): RedirectResponse
    {
        $article->update(['is_published' => ! $article->is_published]);

        return back()->with('success', $article->is_published
            ? __('Article publié.')
            : __('Article dépublié.'));
    }

    public function editArticle(NewsArticle $article): View
    {
        $article->load('source');

        return view('news::admin.articles.edit', compact('article'));
    }

    public function updateArticle(Request $request, NewsArticle $article): RedirectResponse
    {
        $validated = $request->validate([
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'category_tag' => 'nullable|string|max:50',
            'summary' => 'nullable|string|max:2000',
        ]);

        $article->update($validated);

        return redirect()->route('admin.news.articles.index')->with('success', __('Article mis a jour.'));
    }

    public function rescoreArticle(NewsArticle $article, AiSummaryService $aiService): RedirectResponse
    {
        $text = $article->title . '. ' . ($article->description ?? '');
        $result = $aiService->scoreAndSummarize($text);

        if ($result) {
            $article->update([
                'relevance_score' => $result['score'] ?? $article->relevance_score,
                'score_justification' => $result['score_justification'] ?? $article->score_justification,
                'structured_summary' => $result,
                'category_tag' => $result['category'] ?? $article->category_tag,
                'impact_level' => $result['impact'] ?? $article->impact_level,
                'seo_title' => $result['seo_title'] ?? $article->seo_title,
                'meta_description' => $result['meta_description'] ?? $article->meta_description,
                'summary' => $result['hook'] ?? $article->summary,
            ]);

            return back()->with('success', __('Article re-score : :score/10', ['score' => $result['score'] ?? '?']));
        }

        return back()->with('error', __('Erreur lors du re-scoring IA.'));
    }

    public function destroyArticle(NewsArticle $article): RedirectResponse
    {
        $article->delete();

        return back()->with('success', __('Article supprimé.'));
    }
}
