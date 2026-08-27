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

        // Filtre « vérifications » (module « vérification », 2026-08-21) : ne garde que les
        // fiches qui vérifient une affirmation circulant ailleurs. Le scope factChecked() du
        // modèle est la définition UNIQUE de « ce qu'est une vérification » côté requête -
        // réutilisée telle quelle par la route dédiée /verifications, qui ne fait qu'activer ce
        // filtre plutôt que de dupliquer tout cet index.
        $factCheckOnly = $request->boolean('verifications');
        if ($factCheckOnly) {
            $query->factChecked();
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

        // Exposition publique de /verifications (2026-08-27, décision panel notée 82/100,
        // docs/specs/2026-08-27-exposition-verifications-panel.md) : compte les fiches
        // vérifiées pour piloter l'affichage du chip de filtre en tête de liste - jamais
        // affiché si ce compte est à zéro (un filtre qui ne trierait rien serait une
        // promesse vide). Réutilise scopeFactChecked(), la même définition UNIQUE de « ce
        // qu'est une fiche vérifiée » que le filtre ci-dessus et que la route /verifications
        // (DRY strict, aucune seconde définition).
        $factCheckCount = NewsArticle::published()->factChecked()->count();

        $filters = [
            'category' => $category,
            'period' => $period,
            'sort' => $sort,
            'q' => $search,
            'verifications' => $factCheckOnly,
        ];

        return view('news::public.index', compact('articles', 'categories', 'filters', 'factCheckCount'));
    }

    /**
     * Page publique des vérifications (module « vérification », 2026-08-21).
     *
     * Ne duplique RIEN : elle active le filtre puis délègue à index(), qui garde la pagination,
     * la recherche, le tri et le rendu des cartes. Son intérêt est d'exister comme adresse
     * stable et citable - c'est aussi ce que Google attend d'un éditeur qui balise des
     * vérifications : une pratique suivie, visible en un endroit, plutôt qu'une page isolée.
     */
    public function verifications(Request $request): View
    {
        $request->merge(['verifications' => true]);

        return $this->index($request);
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
        // 2026-08-13, section 4.4), servi en 410 propre via la même vue que le retrait
        // (news::public.gone, DRY - voir bloc isRetired() ci-dessus) plutôt qu'un 404 brut
        // (2026-08-20) - une fiche sans résumé exploitable est publiée mais inexploitable pour
        // le visiteur, jamais une simple absence de ressource.
        // MCP: SELF (<5 lignes)
        // RAISON: la colonne description ne porte plus aucun texte de repli ; un 404 brut est
        // mauvais pour l'UX et le SEO là où un 410 explicite l'est moins.
        if (! $article->hasExploitableSummary()) {
            return response()->view('news::public.gone', ['article' => $article], 410);
        }

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
