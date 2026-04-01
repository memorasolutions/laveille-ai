<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\News\Models\NewsArticle;
use Modules\Settings\Facades\Settings;

class PublicNewsController extends Controller
{
    public function index(): View
    {
        $articles = NewsArticle::published()
            ->whereNotNull('structured_summary')
            ->where('relevance_score', '>=', (int) config('news.min_relevance_score', 7))
            ->recent()
            ->with('source')
            ->limit(50)
            ->get();

        $grouped = $articles->groupBy('category_tag');

        // Fallback : si aucun article structuré, montrer les anciens
        if ($articles->isEmpty()) {
            $articles = NewsArticle::published()
                ->recent()
                ->with('source')
                ->paginate((int) Settings::get('news.articles_per_page', 20));

            return view('news::public.index', compact('articles'));
        }

        return view('news::public.index', compact('articles', 'grouped'));
    }

    public function show(NewsArticle $article): View
    {
        abort_if(! $article->is_published, 404);

        $article->load('source');

        return view('news::public.show', compact('article'));
    }
}
