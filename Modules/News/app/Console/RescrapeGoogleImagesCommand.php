<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\NewsImageService;

class RescrapeGoogleImagesCommand extends Command
{
    protected $signature = 'news:rescrape-google-images {--dry-run}';

    protected $description = 'Re-scraper les images des articles News qui ont le logo Google News';

    public function handle(): int
    {
        $articles = NewsArticle::where(function ($query) {
            $query->where('image_url', 'like', '%google%')
                ->orWhere('image_url', 'like', '%gstatic%');
        })->orWhereIn('image_url', function ($sub) {
            $sub->select('image_url')
                ->from((new NewsArticle)->getTable())
                ->whereNotNull('image_url')
                ->groupBy('image_url')
                ->havingRaw('COUNT(*) > 2');
        })->get();

        if ($articles->isEmpty()) {
            $this->info('Aucun article a traiter.');

            return self::SUCCESS;
        }

        $this->info("Articles trouves : {$articles->count()}");
        $updated = 0;

        foreach ($articles as $article) {
            $extracted = app(\Modules\News\Services\ContentExtractor::class)->extract($article->resolved_url ?? $article->url);
            $ogImage = $extracted['image'] ?? null;

            if (! $ogImage) {
                $this->warn("[{$article->id}] Aucune og:image pour {$article->url}");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("[{$article->id}] DRY-RUN: {$ogImage}");
                $updated++;

                continue;
            }

            $localPath = app(NewsImageService::class)->processFromUrl($ogImage, $article->id);

            if (! $localPath) {
                $this->error("[{$article->id}] Echec telechargement");

                continue;
            }

            $article->update(['image_url' => $localPath]);
            $this->info("[{$article->id}] OK: {$localPath}");
            $updated++;
        }

        $mode = $this->option('dry-run') ? 'DRY RUN' : 'APPLIED';
        $this->info("{$mode}: {$updated}/{$articles->count()} images mises a jour.");

        return self::SUCCESS;
    }

    // scrapeOgImage supprimé — utilise ContentExtractor::extract() (zéro duplication)
}
