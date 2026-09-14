<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: pages de DOSSIER regroupant les actualités qui partagent une entité nommée.
 * MCP: squelette généré par hermes (model_invoke code), corrigé ici - il visait la table
 *      « news_article_entity » au singulier, qui n'existe pas, et faisait un N+1 sur l'index.
 * RAISON: le site doit remonter en qualité pour AdSense. Un dossier riche remplace une poignée
 *         de brèves éparses : le lecteur y gagne, le robot aussi.
 *
 * La règle « qu'est-ce qu'un dossier servable » vit dans EntityDossierService, PAS ici : le
 * sitemap la consomme aussi, et deux copies finiraient par annoncer à Google des pages en 404.
 */

namespace Modules\News\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsArticleEntity;
use Modules\News\Services\EntityDossierService;

class EntityDossierController extends Controller
{
    public function __construct(private readonly EntityDossierService $dossiers) {}

    public function index(): View
    {
        return view('news::public.dossiers-index', ['dossiers' => $this->dossiers->dossiers()]);
    }

    public function show(string $slug): View
    {
        // Le même refus qu'à l'index, et pas seulement l'absence du lien : la route est publique,
        // un dossier écarté doit répondre 404 même quand on tape son adresse directement.
        abort_if($this->dossiers->estExclu($slug), 404);

        $entite = NewsArticleEntity::query()->where('entity_slug', $slug)->first();

        abort_if($entite === null, 404);

        $ids = NewsArticleEntity::query()
            ->join('news_articles', 'news_articles.id', '=', 'news_article_entities.news_article_id')
            ->where('news_article_entities.entity_slug', $slug)
            ->where('news_articles.is_published', true)
            ->whereNull('news_articles.retired_at')
            ->pluck('news_articles.id');

        $total = $ids->count();

        abort_if($total < $this->dossiers->seuil(), 404);

        $articles = NewsArticle::query()->whereIn('id', $ids)
            ->orderByDesc('pub_date')->paginate(20)->withQueryString();

        $bornes = NewsArticle::query()->whereIn('id', $ids)
            ->selectRaw('min(pub_date) as premiere, max(pub_date) as derniere')->first();

        // Dossiers voisins : les entités qui reviennent le plus souvent AVEC celle-ci. C'est ce
        // qui transforme une liste isolée en réseau navigable, et donne au lecteur une raison
        // de rester plutôt qu'une impasse.
        $entitesVoisines = NewsArticleEntity::query()
            ->whereIn('news_article_id', $ids)
            ->where('entity_slug', '!=', $slug)
            ->whereNotIn('entity_slug', $this->dossiers->slugsExclus())
            ->groupBy('entity_slug', 'entity_label')
            ->havingRaw('count(*) >= 2')
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(5)
            ->get(['entity_slug', 'entity_label', DB::raw('count(*) as n')]);

        return view('news::public.dossier', [
            'entite' => $entite->entity_label,
            'slug' => $slug,
            'articles' => $articles,
            'total' => $total,
            // Aucune date inventée : si la borne manque, la vue n'affichera pas la période.
            'premiereDate' => $bornes?->premiere ? Carbon::parse($bornes->premiere) : null,
            'derniereDate' => $bornes?->derniere ? Carbon::parse($bornes->derniere) : null,
            'entitesVoisines' => $entitesVoisines,
        ]);
    }
}
