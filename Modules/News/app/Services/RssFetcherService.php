<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

                $itemTitle = $item->get_title() ?? 'Sans titre';
                $itemUrl = $item->get_permalink() ?? $source->url;

                if (self::isDuplicate($itemUrl, $itemTitle)) {
                    Log::info("News dedup: skipped duplicate '{$itemTitle}' from {$source->name}");

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

                // og:image sera extraite par ContentExtractor après résolution URL

                $article = NewsArticle::create([
                    'news_source_id' => $source->id,
                    'title' => $itemTitle,
                    'guid' => $guid,
                    'url' => $itemUrl,
                    'description' => strip_tags($item->get_description() ?? ''),
                    'pub_date' => $item->get_date('Y-m-d H:i:s') ? Carbon::parse($item->get_date('Y-m-d H:i:s')) : $now,
                    'author' => $item->get_author() ? $item->get_author()->get_name() : null,
                    'image_url' => $imageUrl,
                    'is_published' => false,
                ]);

                // Résoudre URL Google News vers article original
                if (GoogleNewsResolver::isGoogleNewsUrl($article->url)) {
                    $resolvedUrl = app(GoogleNewsResolver::class)->resolve($article->url);
                    if ($resolvedUrl && $resolvedUrl !== $article->url) {
                        $article->update(['resolved_url' => $resolvedUrl]);
                    }
                }
                $articleUrl = $article->resolved_url ?? $article->url;

                // Extraire contenu complet pour résumé IA + image
                $extracted = app(ContentExtractor::class)->extract($articleUrl);
                if ($extracted) {
                    if (! $imageUrl && $extracted['image']) {
                        $imageUrl = $extracted['image'];
                    }
                    if ($extracted['word_count'] > 100 && mb_strlen($extracted['content']) > mb_strlen($article->description ?? '')) {
                        $article->update(['description' => Str::limit($extracted['content'], 5000)]);
                    }
                }

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

    // scrapeOgImage supprimé — utiliser ContentExtractor::extractOgImage() (zéro duplication)

    /**
     * Vérifier si un article similaire existe déjà (déduplication cross-sources).
     */
    private static function isDuplicate(string $url, string $title): bool
    {
        $normalizedUrl = self::normalizeUrl($url);

        if (NewsArticle::where('url', $normalizedUrl)
            ->orWhere('resolved_url', $normalizedUrl)
            ->orWhere('url', $url)
            ->orWhere('resolved_url', $url)
            ->exists()) {
            return true;
        }

        $threeDaysAgo = Carbon::now()->subDays(3);
        $normalizedInputTitle = self::normalizeTitle($title);

        if (mb_strlen($normalizedInputTitle) < 10) {
            return false;
        }

        $existingArticles = NewsArticle::where('created_at', '>=', $threeDaysAgo)
            ->pluck('title');

        foreach ($existingArticles as $existingTitle) {
            similar_text($normalizedInputTitle, self::normalizeTitle($existingTitle), $percent);
            if ($percent > 85) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (! $parsed || ! isset($parsed['host'])) {
            return $url;
        }

        $host = strtolower(ltrim($parsed['host'], 'www.'));
        $path = rtrim($parsed['path'] ?? '', '/') ?: '/';

        return 'https://'.$host.$path;
    }

    private static function normalizeTitle(string $title): string
    {
        $title = mb_strtolower($title);
        $title = preg_replace('/[^\p{L}\p{N}\s]/u', '', $title);

        return trim(preg_replace('/\s+/', ' ', $title));
    }
}
