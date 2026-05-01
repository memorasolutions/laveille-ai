<?php

declare(strict_types=1);

namespace Modules\News\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\RssFetcherService;
use Modules\Settings\Facades\Settings;

class FetchNewsCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'news:fetch {--source= : ID source spécifique} {--force : Forcer même si kill switch actif}';

    protected $description = 'Récupère les articles RSS, score et génère les résumés structurés IA.';

    public function handle(RssFetcherService $fetcher, AiSummaryService $summarizer): int
    {
        if ($this->shouldSkipForKillSwitch('cron.news-fetch')) {
            return self::SUCCESS;
        }

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

                // DEDUP-SKIP : evite resume IA sur doublons cross-source
                if (env('NEWS_DEDUP_SKIP_ENABLED', true) && class_exists(\Modules\News\Services\DedupService::class)) {
                    $isDuplicate = false;
                    try {
                        $candidates = NewsArticle::where('id', '!=', $article->id)
                            ->where('news_source_id', '!=', $article->news_source_id)
                            ->where('created_at', '>=', now()->subDays(2))
                            ->whereNotNull('structured_summary')
                            ->get(['id', 'title', 'url', 'published_at', 'news_source_id']);
                        foreach ($candidates as $cand) {
                            $signals = [];
                            $check = \Modules\News\Services\DedupService::isLikelyDuplicate(
                                ['url' => $article->url, 'title' => $article->title, 'published_at' => $article->published_at, 'source_language' => $source->language],
                                ['url' => $cand->url, 'title' => $cand->title, 'published_at' => $cand->published_at, 'source_language' => $source->language],
                                $signals
                            );
                            if ($check['is_duplicate']) {
                                \Illuminate\Support\Facades\Log::info(sprintf('DEDUP-SKIP: article #%d "%s" doublon de #%d (score=%.3f, reason=%s) [IA evitee]', $article->id, mb_substr($article->title, 0, 60), $cand->id, $check['score'], $check['reason']));
                                $article->update(['is_published' => false, 'summary' => '[doublon detecte - IA evitee]', 'feed_type' => $feedType]);
                                $this->line("  ⊕ Doublon skip IA : {$article->title}");
                                $totalFiltered++;
                                $isDuplicate = true;
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('DEDUP-SKIP error: ' . $e->getMessage());
                    }
                    if ($isDuplicate) { continue; }
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
