<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\RssFetcherService;
use Modules\Settings\Facades\Settings;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch {--source= : ID source spécifique}';

    protected $description = 'Récupère les articles RSS, score et génère les résumés structurés IA.';

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

        $minScore = (int) Settings::get('news.min_relevance_score', 7);
        $maxIa = (int) Settings::get('news.max_ia_articles_per_day', 10);
        $maxTech = (int) Settings::get('news.max_tech_articles_per_day', 5);

        $totalFetched = 0;
        $totalPublished = 0;
        $totalFiltered = 0;

        // Compteurs du jour
        $todayIa = NewsArticle::where('feed_type', 'ia')
            ->where('is_published', true)
            ->whereDate('created_at', today())
            ->count();
        $todayTech = NewsArticle::where('feed_type', 'techno')
            ->where('is_published', true)
            ->whereDate('created_at', today())
            ->count();

        foreach ($sources as $source) {
            $this->info("Récupération : {$source->name}");
            $fetched = $fetcher->fetchSource($source);
            $totalFetched += $fetched;
            $this->line("  {$fetched} nouveaux articles");

            $feedType = $this->detectFeedType($source);

            $toProcess = NewsArticle::where('news_source_id', $source->id)
                ->whereNull('structured_summary')
                ->where('is_published', false)
                ->get();

            foreach ($toProcess as $article) {
                // Pré-filtre mots-clés (gratuit)
                if (! $summarizer->isRelevant($article->title, $article->description)) {
                    $article->update([
                        'is_published' => false,
                        'summary' => '[non pertinent - mots-clés]',
                        'relevance_score' => 0,
                        'feed_type' => $feedType,
                    ]);
                    $this->line("  ⊘ Filtré mots-clés : {$article->title}");
                    $totalFiltered++;
                    continue;
                }

                // Vérifier quota du jour
                if ($feedType === 'ia' && $todayIa >= $maxIa) {
                    $this->line("  ⏸ Quota IA atteint ({$maxIa}/jour)");
                    break;
                }
                if ($feedType === 'techno' && $todayTech >= $maxTech) {
                    $this->line("  ⏸ Quota techno atteint ({$maxTech}/jour)");
                    break;
                }

                // Score + résumé IA (1 seul appel)
                $result = $summarizer->scoreAndSummarize($article->title, $article->description, $source->language);

                if (! $result) {
                    $article->update(['summary' => '[échec IA]', 'feed_type' => $feedType]);
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
                    'feed_type' => $feedType,
                    'seo_title' => \Illuminate\Support\Str::limit($result['seo_title'] ?? '', 250, ''),
                    'meta_description' => \Illuminate\Support\Str::limit($result['meta_description'] ?? '', 250, ''),
                    'summary' => $result['hook'] ?? null,
                    'is_published' => $score >= $minScore,
                ]);

                if ($score >= $minScore) {
                    $totalPublished++;
                    if ($feedType === 'ia') $todayIa++;
                    else $todayTech++;
                    $this->line("  ✓ [{$score}/10] {$article->title}");
                } else {
                    $totalFiltered++;
                    $this->line("  ⊘ [{$score}/10] Non pertinent : {$article->title}");
                }
            }
        }

        $this->info("--- Bilan : {$totalFetched} récupérés, {$totalPublished} publiés, {$totalFiltered} filtrés ---");

        return 0;
    }

    private function detectFeedType(NewsSource $source): string
    {
        $url = mb_strtolower($source->url);
        $name = mb_strtolower($source->name);

        if (str_contains($url, 'intelligence+artificielle') || str_contains($url, 'ai-artificial')
            || str_contains($name, 'ia') || str_contains($name, ' ai')) {
            return 'ia';
        }

        return 'techno';
    }
}
