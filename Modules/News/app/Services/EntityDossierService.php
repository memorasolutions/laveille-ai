<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: définition unique de ce qu'est un DOSSIER thématique servable.
 * MCP: SELF - déplacement de code déjà écrit et testé, pas une génération.
 * RAISON: la règle est consommée par le contrôleur ET par le sitemap. Deux copies
 *         divergeraient, et le sitemap finirait par annoncer à Google des pages en 404 -
 *         le défaut exact qui a tué 244 redirections curatées (ticket #2522).
 */

namespace Modules\News\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticleEntity;
use Modules\News\Models\NewsSource;

class EntityDossierService
{
    /**
     * Sous ce seuil, un dossier n'est pas servi. Ce n'est pas un détail de confort : un dossier
     * de deux fiches EST la page mince qu'on cherche à éliminer. En publier serait remplacer un
     * problème par le même problème, avec une URL de plus.
     */
    public function seuil(): int
    {
        return (int) config('news.dossiers.seuil_minimum', 5);
    }

    /**
     * Les entités qui ne peuvent PAS devenir un dossier. Deux sources qui se complètent :
     *  - la liste éditoriale de config (un média n'est pas un sujet, cf. le commentaire là-bas) ;
     *  - le nom des flux eux-mêmes, qui capture automatiquement tout flux ajouté plus tard.
     * La seconde seule n'attrapait que 2 des 8 médias mesurés en production le 2026-09-14.
     *
     * @return array<int, string>
     */
    public function slugsExclus(): array
    {
        $editoriaux = array_map('strval', (array) config('news.dossiers.medias_exclus', []));

        $flux = NewsSource::query()->pluck('name')
            ->map(fn ($nom) => Str::slug((string) $nom))
            ->filter()->all();

        return array_values(array_unique(array_merge($editoriaux, $flux)));
    }

    public function estExclu(string $slug): bool
    {
        return in_array($slug, $this->slugsExclus(), true);
    }

    /**
     * Les dossiers servables, du plus fourni au moins fourni.
     *
     * @return Collection<int, object>
     */
    public function dossiers(): Collection
    {
        return $this->requete()
            ->orderByDesc(DB::raw('count(*)'))
            ->get([
                'news_article_entities.entity_slug',
                'news_article_entities.entity_label',
                DB::raw('count(*) as total'),
            ]);
    }

    /**
     * Les dossiers auxquels UNE fiche appartient - le maillage qui compte vraiment : 311 fiches
     * qui pointent vers 41 dossiers, plutot que deux pages accessibles par le seul plan de site.
     * Ne renvoie que les dossiers reellement servables, donc jamais un lien vers un 404.
     *
     * @return Collection<int, object>
     */
    public function dossiersPourArticle(int $articleId): Collection
    {
        $slugs = NewsArticleEntity::query()->where('news_article_id', $articleId)
            ->pluck('entity_slug')->unique()->all();

        if ($slugs === []) {
            return collect();
        }

        return $this->requete()
            ->whereIn('news_article_entities.entity_slug', $slugs)
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(6)
            ->get([
                'news_article_entities.entity_slug',
                'news_article_entities.entity_label',
                DB::raw('count(*) as total'),
            ]);
    }

    /**
     * Le socle commun : entités d'actualités publiées, non retirées, non exclues, au-dessus
     * du seuil.
     */
    private function requete(): Builder
    {
        return NewsArticleEntity::query()
            ->join('news_articles', 'news_articles.id', '=', 'news_article_entities.news_article_id')
            ->where('news_articles.is_published', true)
            ->whereNull('news_articles.retired_at')
            ->whereNotIn('news_article_entities.entity_slug', $this->slugsExclus())
            ->groupBy('news_article_entities.entity_slug', 'news_article_entities.entity_label')
            ->havingRaw('count(*) >= ?', [$this->seuil()]);
    }
}
