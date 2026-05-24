<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorProfile;
use Modules\Blog\Models\Article;
use SimplePie\SimplePie;

final class MigrationImportService
{
    public const SUPPORTED_PLATFORMS = [
        'substack' => 'Substack',
        'medium' => 'Medium',
        'wordpress' => 'WordPress',
        'ghost' => 'Ghost',
        'rss_generic' => 'Flux RSS générique',
    ];

    public function importFromRssUrl(int $authorProfileId, string $rssUrl, string $platform): int
    {
        if (! array_key_exists($platform, self::SUPPORTED_PLATFORMS)) {
            throw new Exception("Plateforme non supportée: {$platform}");
        }

        $authorProfile = AuthorProfile::find($authorProfileId);
        if (! $authorProfile || ! $authorProfile->user_id) {
            throw new Exception('Profil auteur invalide');
        }

        $feed = $this->parseRssFeed($rssUrl);
        $items = $feed->get_items(0, 200);
        if (empty($items)) {
            return 0;
        }

        $importedCount = 0;
        foreach ($items as $item) {
            try {
                if ($this->importSingleItem($item, $authorProfile, $platform)) {
                    $importedCount++;
                }
            } catch (Exception $e) {
                Log::warning("Migration import skip: ".$e->getMessage());
            }
        }

        return $importedCount;
    }

    public function previewRssFeed(string $rssUrl): array
    {
        try {
            $feed = $this->parseRssFeed($rssUrl);
            $items = $feed->get_items(0, 10);
            $preview = [];

            foreach ($items as $item) {
                $preview[] = [
                    'title' => $item->get_title() ?: 'Sans titre',
                    'link' => $item->get_permalink() ?: '',
                    'published_at' => $item->get_date('c') ?: now()->toIso8601String(),
                    'excerpt' => mb_substr(strip_tags($item->get_description() ?? ''), 0, 200),
                ];
            }

            return $preview;
        } catch (Exception $e) {
            Log::error('Preview RSS failed: '.$e->getMessage());
            return [];
        }
    }

    public function getSupportedPlatforms(): array
    {
        return self::SUPPORTED_PLATFORMS;
    }

    private function parseRssFeed(string $rssUrl): SimplePie
    {
        $feed = new SimplePie();
        $feed->set_feed_url($rssUrl);
        $feed->set_cache_location(storage_path('framework/cache'));
        $feed->enable_cache(false);
        $feed->set_timeout(20);
        $feed->init();
        $feed->handle_content_type();

        if ($feed->error()) {
            throw new Exception('SimplePie error: '.$feed->error());
        }

        return $feed;
    }

    private function importSingleItem($item, AuthorProfile $authorProfile, string $platform): bool
    {
        $title = $item->get_title() ?: 'Sans titre';
        $link = $item->get_permalink();
        if (! $link) {
            return false;
        }

        $slug = Str::slug($title);

        $existingBySlug = Article::where('slug', $slug)->exists();
        if ($existingBySlug) {
            return false;
        }

        $contentHtml = $item->get_content();

        Article::create([
            'title' => $title,
            'slug' => $slug,
            'content' => $contentHtml,
            'excerpt' => mb_substr(strip_tags($contentHtml ?? ''), 0, 500),
            'status' => 'draft',
            'user_id' => $authorProfile->user_id,
            'published_at' => $item->get_date('Y-m-d H:i:s'),
            'meta' => ['source' => $link, 'imported_from' => $platform],
        ]);

        return true;
    }
}
