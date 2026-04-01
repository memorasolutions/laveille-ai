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
                        $imageUrl = $enclosure->get_link();
                    }
                }

                // Scrape og:image si pas d'image dans le RSS
                if (! $imageUrl) {
                    $imageUrl = $this->scrapeOgImage($item->get_permalink());
                }

                NewsArticle::create([
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
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; LaVeilleBot/1.0)'])
                ->timeout(10)
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
