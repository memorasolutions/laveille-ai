<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\ContentExtractor;
use Modules\News\Services\GoogleNewsResolver;
use Modules\News\Services\NewsImageService;

class ReprocessArticlesCommand extends Command
{
    protected $signature = 'news:reprocess {--google-only : Uniquement les articles Google News} {--unresolved-only : Uniquement ceux sans resolved_url} {--limit=10 : Nombre max d\'articles} {--dry-run}';

    protected $description = 'Re-traiter les articles News existants avec le nouveau pipeline';

    public function handle(): int
    {
        $query = NewsArticle::with('source')
            ->orderByDesc('id')
            ->limit((int) $this->option('limit'));

        if ($this->option('google-only')) {
            $googleSourceIds = NewsSource::where('name', 'like', '%Google%')->pluck('id');
            $query->whereIn('news_source_id', $googleSourceIds);
        }

        if ($this->option('unresolved-only')) {
            $query->whereNull('resolved_url');
        }

        $articles = $query->get();
        $this->info("Articles a traiter : {$articles->count()}");

        $updated = 0;
        $errors = 0;

        foreach ($articles as $article) {
            $this->line("[{$article->id}] {$article->title}");

            // Résolution Google News
            $resolvedUrl = $article->resolved_url;
            if (GoogleNewsResolver::isGoogleNewsUrl($article->url) && ! $resolvedUrl) {
                $resolvedUrl = app(GoogleNewsResolver::class)->resolve($article->url);
                if ($resolvedUrl && $resolvedUrl !== $article->url && ! $this->option('dry-run')) {
                    $article->update(['resolved_url' => $resolvedUrl]);
                }
                $this->line("  resolved: " . ($resolvedUrl ? Str::limit($resolvedUrl, 60) : 'FAIL'));
            }

            // Extraction contenu
            $extractUrl = $resolvedUrl ?? $article->url;
            $extracted = app(ContentExtractor::class)->extract($extractUrl);

            if (! $extracted) {
                $this->warn("  extraction FAIL");
                $errors++;

                continue;
            }

            $this->line("  content: {$extracted['word_count']} mots, image: " . ($extracted['image'] ? 'OUI' : 'NON'));

            if ($this->option('dry-run')) {
                $updated++;

                continue;
            }

            $updateData = [];

            // Image : télécharger si disponible
            if ($extracted['image']) {
                $newImage = app(NewsImageService::class)->processFromUrl($extracted['image'], $article->id);
                if ($newImage) {
                    $updateData['image_url'] = $newImage;
                    $this->line("  image: OK");
                }
            }

            // Description : enrichir avec le contenu complet
            if ($extracted['word_count'] > 100 && mb_strlen($extracted['content']) > mb_strlen($article->description ?? '')) {
                $updateData['description'] = Str::limit($extracted['content'], 5000);
            }

            // Résumé IA si contenu suffisant
            if ($extracted['word_count'] > 200) {
                $aiResult = app(AiSummaryService::class)->scoreAndSummarize(
                    $extracted['title'] ?: $article->title,
                    $extracted['content']
                );

                if ($aiResult && isset($aiResult['score'])) {
                    $updateData['relevance_score'] = $aiResult['score'];
                    $updateData['structured_summary'] = $aiResult;
                    $updateData['category_tag'] = mb_substr((string) ($aiResult['category'] ?? $article->category_tag ?? ''), 0, 50);
                    $updateData['impact_level'] = mb_substr((string) ($aiResult['impact'] ?? $article->impact_level ?? ''), 0, 10);
                    $updateData['seo_title'] = mb_substr((string) ($aiResult['seo_title'] ?? $article->seo_title ?? ''), 0, 200);
                    $updateData['meta_description'] = mb_substr((string) ($aiResult['meta_description'] ?? $article->meta_description ?? ''), 0, 200);
                    $updateData['summary'] = $aiResult['hook'] ?? $article->summary;
                    $cat = $aiResult['category'] ?? '?';
                    $this->info("  IA: score={$aiResult['score']} cat={$cat}");
                }
            }

            if (! empty($updateData)) {
                $article->update($updateData);
                $updated++;
            }
        }

        $mode = $this->option('dry-run') ? 'DRY RUN' : 'APPLIED';
        $this->info("{$mode}: {$updated} mis a jour, {$errors} erreurs sur {$articles->count()} articles");

        return self::SUCCESS;
    }
}
