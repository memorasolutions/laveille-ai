<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\RssFetcherService;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch {--source= : ID source spécifique}';

    protected $description = 'Récupère les articles RSS, filtre par pertinence IA, génère les résumés structurés.';

    public function handle(RssFetcherService $fetcher, AiSummaryService $summarizer): int
    {
        $query = NewsSource::active();

        if ($sourceId = $this->option('source')) {
            $query->where('id', $sourceId);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info('Aucune source active trouvée.');
            return 0;
        }

        $minScore = (int) config('news.min_relevance_score', 7);
        $maxIa = (int) config('news.max_articles_ia', 10);
        $maxTechno = (int) config('news.max_articles_techno', 5);

        $totalFetched = 0;
        $candidates = collect();

        // Phase 1 : récupérer les articles RSS
        foreach ($sources as $source) {
            $this->info("Récupération : {$source->name}");
            $fetched = $fetcher->fetchSource($source);
            $totalFetched += $fetched;
            $this->line("  {$fetched} nouveaux articles");
        }

        // Phase 2 : filtrer + scorer + résumer
        $toProcess = NewsArticle::whereNull('structured_summary')
            ->where('is_published', false)
            ->whereNull('relevance_score')
            ->latest('pub_date')
            ->limit(60)
            ->get();

        $this->info("Articles à traiter : {$toProcess->count()}");

        foreach ($toProcess as $article) {
            // Filtre rapide par mots-clés
            if (! $summarizer->isRelevant($article->title, $article->description)) {
                $article->update([
                    'is_published' => false,
                    'relevance_score' => 0,
                    'score_justification' => 'Filtré par mots-clés : aucun terme IA/tech détecté',
                ]);
                $this->line("  ⊘ Mots-clés : {$article->title}");
                continue;
            }

            // Appel IA : score + résumé structuré
            $result = $summarizer->structuredSummary(
                $article->title,
                $article->description,
                $article->source->language ?? 'fr'
            );

            if (! $result) {
                $this->warn("  ✗ Échec IA : {$article->title}");
                continue;
            }

            $score = (int) ($result['score'] ?? 0);

            $article->update([
                'relevance_score' => $score,
                'score_justification' => $result['score_justification'] ?? null,
                'structured_summary' => $result,
                'category_tag' => $result['category'] ?? null,
                'impact_level' => $result['impact'] ?? null,
                'seo_title' => $result['seo_title'] ?? null,
                'meta_description' => $result['meta_description'] ?? null,
                'summary' => $result['hook'] ?? null,
                'feed_type' => $article->source->category ?? 'ia',
                'is_published' => $score >= $minScore,
            ]);

            $emoji = $score >= $minScore ? '✓' : '⊘';
            $this->line("  {$emoji} [{$score}/10] {$article->title}");
        }

        // Phase 3 : limiter le quota (garder seulement les meilleurs)
        $this->applyDailyQuota($maxIa, $maxTechno, $minScore);

        $published = NewsArticle::where('is_published', true)->whereDate('pub_date', today())->count();
        $this->info("--- Bilan : {$totalFetched} récupérés, {$published} publiés aujourd'hui ---");

        return 0;
    }

    private function applyDailyQuota(int $maxIa, int $maxTechno, int $minScore): void
    {
        // Garder les top N par feed_type pour aujourd'hui
        foreach (['ia' => $maxIa, 'techno' => $maxTechno] as $type => $max) {
            $published = NewsArticle::where('feed_type', $type)
                ->where('is_published', true)
                ->whereDate('pub_date', today())
                ->orderByDesc('relevance_score')
                ->get();

            if ($published->count() > $max) {
                $toUnpublish = $published->slice($max);
                NewsArticle::whereIn('id', $toUnpublish->pluck('id'))
                    ->update(['is_published' => false]);
                $this->line("  Quota {$type} : {$toUnpublish->count()} articles dépubliés (max {$max})");
            }
        }
    }
}
