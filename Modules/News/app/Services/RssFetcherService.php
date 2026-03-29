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

                NewsArticle::create([
                    'news_source_id' => $source->id,
                    'title' => $item->get_title() ?? 'Sans titre',
                    'guid' => $guid,
                    'url' => $item->get_permalink() ?? $source->url,
                    'description' => strip_tags($item->get_description() ?? ''),
                    'pub_date' => $item->get_date('Y-m-d H:i:s') ? Carbon::parse($item->get_date('Y-m-d H:i:s')) : $now,
                    'author' => $item->get_author() ? $item->get_author()->get_name() : null,
                    'image_url' => $imageUrl,
                    'is_published' => true,
                ]);
                $count++;
            }

            $source->update(['last_fetched_at' => $now]);

        } catch (\Throwable $e) {
            Log::error("Error fetching RSS from {$source->url}: ".$e->getMessage());
        }

        return $count;
    }
}
