<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    // ACTION : type de retour élargi à Response (chantier AdSense « faible valeur »,
    // 2026-08-18) - response()->view(..., 410) retourne Illuminate\Http\Response, pas View ;
    // sans cet élargissement, le TypeError est immédiat au premier appel sur une fiche retirée.
    // MCP: SELF (<5 lignes)
    // RAISON: correction nécessaire pour que la réponse 410 soit valide côté PHP.
    public function show(NewsArticle $article): View|Response
    {
        // ACTION : chantier AdSense « faible valeur » (2026-08-18) - une fiche retirée
        // (retired_at non nul) résout encore par route model binding (retired_at n'est pas
        // filtré à la résolution, volontairement - voir docblock de scopePublished() dans
        // NewsArticle) : elle doit répondre 410 Gone AVANT tout autre traitement de cette
        // méthode (vue dédiée, jamais l'erreur 404 générique), c'est un retrait volontaire au
        // sens de Google, pas une disparition.
        // MCP: SELF (<5 lignes)
        // RAISON: design doc du chantier - retrait SEO-sûr et réversible.
        if ($article->isRetired()) {
            return response()->view('news::public.gone', ['article' => $article], 410);
        }

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
