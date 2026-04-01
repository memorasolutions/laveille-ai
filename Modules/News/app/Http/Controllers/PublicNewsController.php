<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsArticle;

class PublicNewsController extends Controller
{
    public function index(Request $request): View
    {
        $query = NewsArticle::published()->with('source');

        // Filtre catégorie
        $category = $request->input('category');
        if ($category) {
            $query->where('category_tag', $category);
        }

        // Filtre période
        $period = $request->input('period');
        match ($period) {
            'today' => $query->whereDate('pub_date', now()->toDateString()),
            'week' => $query->where('pub_date', '>=', now()->subWeek()),
            'month' => $query->where('pub_date', '>=', now()->subMonth()),
            default => null,
        };

        // Recherche textuelle
        $search = $request->input('q');
        if ($search) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('seo_title', 'like', "%{$search}%"));
        }

        // Tri
        $sort = $request->input('sort', 'date');
        if ($sort === 'score') {
            $query->orderBy('relevance_score', 'desc');
        } else {
            $query->orderBy('pub_date', 'desc');
        }

        $articles = $query->paginate(20);

        // Catégories avec compteurs
        $categories = NewsArticle::published()
            ->whereNotNull('category_tag')
            ->select('category_tag', DB::raw('COUNT(*) as count'))
            ->groupBy('category_tag')
            ->orderBy('count', 'desc')
            ->get();

        $filters = [
            'category' => $category,
            'period' => $period,
            'sort' => $sort,
            'q' => $search,
        ];

        return view('news::public.index', compact('articles', 'categories', 'filters'));
    }

    public function show(NewsArticle $article): View
    {
        abort_if(! $article->is_published, 404);

        $article->load('source');

        return view('news::public.show', compact('article'));
    }
}
