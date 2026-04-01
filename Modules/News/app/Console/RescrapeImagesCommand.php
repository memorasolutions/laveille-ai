<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\NewsImageService;

class RescrapeImagesCommand extends Command
{
    protected $signature = 'news:rescrape-images {--limit=50}';

    protected $description = 'Re-scraper les og:image des articles sans image et les optimiser en WebP';

    public function handle(): void
    {
        $limit = (int) $this->option('limit');

        $articles = NewsArticle::where('is_published', true)
            ->where(fn ($q) => $q->whereNull('image_url')->orWhere('image_url', ''))
            ->whereNotNull('url')
            ->limit($limit)
            ->get();

        $total = $articles->count();
        $success = 0;

        $this->info("Traitement de {$total} articles sans image...");

        $imageService = app(NewsImageService::class);

        foreach ($articles as $article) {
            try {
                // Suivre les redirects (Google News → article original)
                $response = Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'])
                    ->timeout(15)
                    ->withOptions(['allow_redirects' => ['max' => 5]])
                    ->get($article->url);

                if (! $response->successful()) {
                    $this->warn("  HTTP {$response->status()} — {$article->url}");

                    continue;
                }

                $html = $response->body();
                $ogImage = $this->extractOgImage($html);

                if (! $ogImage) {
                    $this->warn("  Pas d'og:image — #{$article->id} {$article->title}");

                    continue;
                }

                $localPath = $imageService->processFromUrl($ogImage, $article->id);
                if (! $localPath) {
                    $this->warn("  Image trop petite ou invalide — #{$article->id}");

                    continue;
                }

                $article->update(['image_url' => $localPath]);
                $success++;
                $this->info("  OK #{$article->id} → {$localPath}");
            } catch (\Throwable $e) {
                $this->warn("  Erreur #{$article->id}: {$e->getMessage()}");
            }
        }

        $this->info("Terminé : {$success}/{$total} images récupérées.");
    }

    private function extractOgImage(string $html): ?string
    {
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }
}
