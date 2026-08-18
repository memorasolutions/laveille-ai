<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\ViewCounterService;
use Modules\News\Models\NewsArticle;

class PublicNewsController extends Controller
{
    public function index(Request $request): View
    {
        $query = NewsArticle::published()->with('source', 'tools');

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
        // Élagage SEO : une actualité marquée "gone" renvoie 410 (contenu retiré définitivement).
        abort_if(($article->seo_status ?? 'index') === 'gone', 410);
        // ACTION : garde-fou permanent (design doc "Actus - zéro copie du texte source",
        // 2026-08-13, section 4.4) - une fiche sans résumé exploitable ne peut jamais être
        // servie publiquement avec un corps vide, quelle qu'en soit la cause.
        // MCP: SELF (<5 lignes)
        // RAISON: la colonne description ne porte plus aucun texte de repli.
        abort_if(! $article->hasExploitableSummary(), 404);

        // Incident 2026-08-13 (mesuré : rapport vues/clics réels de 8 à 487x selon la
        // fiche - cause de la désindexation erronée de 183 fiches par l'élagage SEO).
        // increment views_count délégué au service partagé (filtre robots réel +
        // déduplication rapprochée, jamais de casse de page).
        ViewCounterService::record($article, 'views_count');
        // ACTION: charger source + outils liés publiés (maillage SEO, évite N+1 en vue)
        // MCP: SELF (<5 lignes)
        // RAISON: bloc « Outils mentionnés » dans show.blade
        $article->load(['source', 'tools' => fn ($q) => $q->where('status', 'published')]);

        // Article précédent (même catégorie, puis toutes)
        $previousArticle = NewsArticle::published()
            ->where('category_tag', $article->category_tag)
            ->where('pub_date', '<', $article->pub_date)
            ->orderBy('pub_date', 'desc')
            ->first()
            ?? NewsArticle::published()
                ->where('pub_date', '<', $article->pub_date)
                ->orderBy('pub_date', 'desc')
                ->first();

        // Article suivant (même catégorie, puis toutes)
        $nextArticle = NewsArticle::published()
            ->where('category_tag', $article->category_tag)
            ->where('pub_date', '>', $article->pub_date)
            ->orderBy('pub_date', 'asc')
            ->first()
            ?? NewsArticle::published()
                ->where('pub_date', '>', $article->pub_date)
                ->orderBy('pub_date', 'asc')
                ->first();

        // ACTION : articles connexes par ENTITÉS partagées d'abord (arbitrage panel 2026-08-17),
        // repli catégorie pour compléter - point d'entrée unique NewsArticle::relatedFor() (DRY).
        // MCP: SELF (<5 lignes)
        // RAISON: connexes réellement pertinents sans modération.
        $relatedArticles = NewsArticle::relatedFor($article, 3);

        return view('news::public.show', compact('article', 'previousArticle', 'nextArticle', 'relatedArticles'));
    }
}
