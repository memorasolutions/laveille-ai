<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use SimplePie\SimplePie;

class RssFetcherService
{
    public function fetchSource(NewsSource $source): int
    {
        $count = 0;

        try {
            $feed = new SimplePie;
            $feed->set_feed_url($source->url);
            $feed->set_cache_location(storage_path('framework/cache'));
            $feed->enable_cache(false);
            $feed->set_timeout(15);
            $feed->init();
            $feed->handle_content_type();

            if ($feed->error()) {
                Log::warning("RSS feed error for {$source->url}: ".$feed->error());

                return 0;
            }

            $now = Carbon::now();

            foreach ($feed->get_items(0, 20) as $item) {
                $guid = $item->get_id() ?: md5($item->get_permalink().$item->get_title());

                if (NewsArticle::where('guid', $guid)->exists()) {
                    continue;
                }

                $imageUrl = null;
                if ($enclosure = $item->get_enclosure()) {
                    $type = $enclosure->get_type() ?? '';
                    if (str_starts_with($type, 'image/')) {
                        $link = $enclosure->get_link();
                        // Ignorer les logos/images Google News (pas l'image de l'article)
                        $isGoogleImage = $link && preg_match('#(google\.com|googleusercontent\.com|gstatic\.com)#i', $link);
                        if (! $isGoogleImage) {
                            $imageUrl = $link;
                        }
                    }
                }

                // Scrape og:image si pas d'image valide dans le RSS
                if (! $imageUrl) {
                    $imageUrl = $this->scrapeOgImage($item->get_permalink());
                }

                $article = NewsArticle::create([
                    'news_source_id' => $source->id,
                    'title' => $item->get_title() ?? 'Sans titre',
                    'guid' => $guid,
                    'url' => $item->get_permalink() ?? $source->url,
                    'description' => strip_tags($item->get_description() ?? ''),
                    'pub_date' => $item->get_date('Y-m-d H:i:s') ? Carbon::parse($item->get_date('Y-m-d H:i:s')) : $now,
                    'author' => $item->get_author() ? $item->get_author()->get_name() : null,
                    'image_url' => $imageUrl,
                    'is_published' => false,
                ]);

                // Optimiser l'image localement (WebP 1200x630)
                $localPath = null;
                if ($imageUrl) {
                    $localPath = app(NewsImageService::class)->processFromUrl($imageUrl, $article->id);
                }
                // Fallback : générer image OG avec logo + titre si pas d'image
                if (! $localPath) {
                    $localPath = NewsImageService::generateFallbackImage(
                        $article->id,
                        $article->seo_title ?? $article->title,
                        $article->category_tag
                    );
                }
                if ($localPath) {
                    $article->update(['image_url' => $localPath]);
                }

                $count++;
            }

            $source->update(['last_fetched_at' => $now]);

        } catch (\Throwable $e) {
            Log::error("Error fetching RSS from {$source->url}: ".$e->getMessage());
        }

        return $count;
    }

    /**
     * Scrape og:image d'une URL d'article.
     */
    private function scrapeOgImage(?string $url): ?string
    {
        if (! $url) return null;

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'])
                ->timeout(15)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (! $response->successful()) return null;

            $html = $response->body();

            // og:image
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
                return $m[1];
            }
            // Reverse order (content before property)
            if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/', $html, $m)) {
                return $m[1];
            }
        } catch (\Throwable $e) {
            Log::debug("og:image scrape failed for {$url}: {$e->getMessage()}");
        }

        return null;
    }
}
