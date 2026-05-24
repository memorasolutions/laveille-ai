<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Modules\Authors\Models\CurationItem;
use SimplePie\SimplePie;

final class CurationInboxService
{
    public function saveLink(int $authorProfileId, string $url, ?string $note = null, array $tags = [], string $source = 'manual'): CurationItem
    {
        if (! in_array($source, ['manual', 'bookmarklet', 'rss'], true)) {
            throw new \InvalidArgumentException('Invalid source provided.');
        }

        $ogData = $this->parseOpenGraph($url);

        return CurationItem::create([
            'author_profile_id' => $authorProfileId,
            'url' => $url,
            'title' => $ogData['title'],
            'excerpt' => $ogData['description'],
            'thumbnail' => $ogData['thumbnail'],
            'note' => $note,
            'tags' => $tags,
            'source_type' => $source,
        ]);
    }

    public function parseOpenGraph(string $url): array
    {
        try {
            $response = Http::timeout(10)->get($url);
            $html = $response->body();
        } catch (\Throwable $e) {
            return [
                'title' => $url,
                'description' => '',
                'thumbnail' => '',
            ];
        }

        $ogTags = [
            'title' => '',
            'description' => '',
            'image' => '',
        ];

        preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\']([^"\']*)["\']/i', $html, $titleMatches);
        preg_match('/<meta[^>]*property=["\']og:description["\'][^>]*content=["\']([^"\']*)["\']/i', $html, $descMatches);
        preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']*)["\']/i', $html, $imageMatches);

        if (! empty($titleMatches[1])) {
            $ogTags['title'] = trim($titleMatches[1]);
        }
        if (! empty($descMatches[1])) {
            $ogTags['description'] = trim($descMatches[1]);
        }
        if (! empty($imageMatches[1])) {
            $ogTags['image'] = trim($imageMatches[1]);
        }

        if (empty($ogTags['title'])) {
            preg_match('/<title>([^<]*)<\/title>/i', $html, $fallbackTitle);
            if (! empty($fallbackTitle[1])) {
                $ogTags['title'] = trim($fallbackTitle[1]);
            }
        }

        return [
            'title' => $ogTags['title'] ?: $url,
            'description' => $ogTags['description'] ?: '',
            'thumbnail' => $ogTags['image'] ?: '',
        ];
    }

    public function search(int $authorProfileId, string $query, ?array $tags = null): Collection
    {
        $queryBuilder = CurationItem::where('author_profile_id', $authorProfileId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    ->orWhere('note', 'like', "%{$query}%");
            });

        if ($tags !== null && ! empty($tags)) {
            foreach ($tags as $tag) {
                $queryBuilder->whereJsonContains('tags', $tag);
            }
        }

        return $queryBuilder->get();
    }

    public function markAsUsed(int $itemId, int $articleId): void
    {
        $item = CurationItem::findOrFail($itemId);
        $item->used_in_article_id = $articleId;
        $item->save();
    }

    public function delete(int $itemId): bool
    {
        $item = CurationItem::findOrFail($itemId);
        return (bool) $item->delete();
    }

    public function importRssFeed(int $authorProfileId, string $rssUrl): int
    {
        $feed = new SimplePie();
        $feed->set_feed_url($rssUrl);
        $feed->set_cache_location(storage_path('framework/cache'));
        $feed->enable_cache(false);
        $feed->set_timeout(15);
        $feed->init();
        $feed->handle_content_type();

        if ($feed->error()) {
            return 0;
        }

        $importedCount = 0;

        foreach ($feed->get_items(0, 20) as $item) {
            $link = $item->get_permalink();
            if (! $link) {
                continue;
            }

            $title = $item->get_title() ?: $link;
            $description = strip_tags($item->get_description() ?: '');

            $thumbnail = '';
            if ($enclosure = $item->get_enclosure()) {
                $thumbnail = $enclosure->get_link() ?: '';
            }

            CurationItem::create([
                'author_profile_id' => $authorProfileId,
                'url' => $link,
                'title' => mb_substr($title, 0, 500),
                'excerpt' => mb_substr($description, 0, 1000),
                'thumbnail' => $thumbnail,
                'note' => null,
                'tags' => [],
                'source_type' => 'rss',
            ]);

            $importedCount++;
        }

        return $importedCount;
    }
}
