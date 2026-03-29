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

    protected $description = 'Récupère les articles RSS et génère les résumés IA.';

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

        $totalFetched = 0;
        $totalSummaries = 0;

        foreach ($sources as $source) {
            $this->info("Récupération : {$source->name}");
            $fetched = $fetcher->fetchSource($source);
            $totalFetched += $fetched;
            $this->line("  {$fetched} nouveaux articles");

            $toSummarize = NewsArticle::where('news_source_id', $source->id)
                ->whereNull('summary')
                ->where('is_published', false)
                ->get();

            foreach ($toSummarize as $article) {
                $summary = $summarizer->summarize($article->description, $source->language);

                if ($summary) {
                    $article->update(['summary' => $summary, 'is_published' => true]);
                    $totalSummaries++;
                    $this->line("  ✓ Résumé : {$article->title}");
                } else {
                    $this->warn("  ✗ Échec résumé : {$article->title}");
                }
            }
        }

        $this->info("--- Bilan : {$totalFetched} articles, {$totalSummaries} résumés ---");

        return 0;
    }
}
