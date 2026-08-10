<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Modules\News\Models\NewsArticle;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai - Actus 2.0 : fusion multi-sources des actualités.
 *
 * Contexte d'archives internes injecté dans le prompt IA du groupe (design doc section 6) :
 * requête interne LIKE sur les entités des titres du groupe, aucune dépendance externe, aucun
 * appel réseau. Retourne un résultat VIDE (jamais une liste factice) quand rien de pertinent
 * n'est trouvé - le prompt du groupe ne mentionne alors aucun contexte historique.
 */
class ArchiveContextService
{
    /**
     * @param  array<int, string>  $titles  Titres des membres du groupe.
     * @return array<int, array{title: string, url: string, date: string}>
     */
    public function findRelevant(array $titles, ?int $excludeArticleId = null): array
    {
        $lookbackMonths = (int) config('news.fusion.archive_lookback_months', 6);
        $maxResults = (int) config('news.fusion.archive_max_results', 5);

        $entities = [];
        foreach ($titles as $title) {
            $entities = array_merge($entities, DedupService::extractKeyEntities($title));
        }
        $entities = array_values(array_unique($entities));

        if ($entities === []) {
            return [];
        }

        $articles = NewsArticle::query()
            ->where('is_published', true)
            ->where('pub_date', '>=', now()->subMonths($lookbackMonths))
            ->where('pub_date', '<', now()->subDay())
            ->when($excludeArticleId, fn ($query) => $query->where('id', '!=', $excludeArticleId))
            ->where(function ($query) use ($entities) {
                foreach ($entities as $entity) {
                    $query->orWhere('title', 'LIKE', '%'.$entity.'%');
                }
            })
            ->orderByDesc('pub_date')
            ->limit($maxResults)
            ->get(['id', 'slug', 'title', 'seo_title', 'pub_date']);

        return $articles->map(fn (NewsArticle $article) => [
            'title' => $article->seo_title ?? $article->title,
            'url' => route('news.show', $article),
            'date' => $article->pub_date?->toDateString() ?? '',
        ])->all();
    }
}
