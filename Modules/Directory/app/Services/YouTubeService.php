<?php

namespace Modules\Directory\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    public function searchTutorials(string $toolName, int $maxResults = 10, ?string $toolUrl = null, string $lang = 'fr'): array
    {
        $apiKey = config('directory.youtube_api_key');
        if (empty($apiKey)) {
            return [];
        }

        $query = $lang === 'fr' ? "{$toolName} tutoriel" : "{$toolName} tutorial";

        if ($toolUrl) {
            $domain = parse_url($toolUrl, PHP_URL_HOST);
            if ($domain && ! str_contains($domain, 'producthunt.com')) {
                $query .= ' '.preg_replace('/^www\./', '', $domain);
            }
        }

        $params = [
            'key' => $apiKey,
            'q' => $query,
            'part' => 'id',
            'type' => 'video',
            'order' => 'viewCount',
            'videoDuration' => 'medium',
            'publishedAfter' => Carbon::now()->subMonths(24)->toIso8601String(),
            'maxResults' => $maxResults,
            'relevanceLanguage' => $lang,
        ];

        if ($lang === 'fr') {
            $params['regionCode'] = 'CA';
        }

        try {
            $response = Http::get('https://www.googleapis.com/youtube/v3/search', $params);

            if ($response->failed()) {
                Log::warning('YouTubeService::searchTutorials — échec API', [
                    'tool_name' => $toolName,
                    'lang' => $lang,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return array_map(
                fn ($item) => $item['id']['videoId'],
                array_filter($response->json('items') ?? [], fn ($item) => isset($item['id']['videoId']))
            );
        } catch (\Throwable $e) {
            Log::warning('YouTubeService::searchTutorials — exception', [
                'tool_name' => $toolName,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getVideoDetails(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        try {
            $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                'key' => config('directory.youtube_api_key'),
                'id' => implode(',', $videoIds),
                'part' => 'snippet,statistics,contentDetails',
            ]);

            if ($response->failed()) {
                return [];
            }

            $videos = [];
            foreach ($response->json('items') ?? [] as $item) {
                $snippet = $item['snippet'] ?? [];
                $stats = $item['statistics'] ?? [];
                $content = $item['contentDetails'] ?? [];
                $videoId = $item['id'] ?? '';
                $thumbs = $snippet['thumbnails'] ?? [];
                $channelId = $snippet['channelId'] ?? '';

                $videos[] = [
                    'video_id' => $videoId,
                    'title' => $snippet['title'] ?? '',
                    'channel_name' => $snippet['channelTitle'] ?? '',
                    'channel_url' => $channelId ? "https://www.youtube.com/channel/{$channelId}" : '',
                    'thumbnail' => $thumbs['high']['url'] ?? $thumbs['medium']['url'] ?? $thumbs['default']['url'] ?? '',
                    'url' => "https://youtube.com/watch?v={$videoId}",
                    'duration_seconds' => self::parseDuration($content['duration'] ?? 'PT0S'),
                    'view_count' => (int) ($stats['viewCount'] ?? 0),
                    'like_count' => (int) ($stats['likeCount'] ?? 0),
                    'published_at' => $snippet['publishedAt'] ?? null,
                ];
            }

            return $videos;
        } catch (\Throwable $e) {
            Log::warning('YouTubeService::getVideoDetails — exception', ['message' => $e->getMessage()]);

            return [];
        }
    }

    public static function parseDuration(string $iso8601): int
    {
        $h = $m = $s = 0;
        if (preg_match('/(\d+)H/', $iso8601, $matches)) {
            $h = (int) $matches[1];
        }
        if (preg_match('/(\d+)M/', $iso8601, $matches)) {
            $m = (int) $matches[1];
        }
        if (preg_match('/(\d+)S/', $iso8601, $matches)) {
            $s = (int) $matches[1];
        }

        return ($h * 3600) + ($m * 60) + $s;
    }

    public function scoreAndFilter(array $videos, ?string $toolName = null, int $minViews = 5000): array
    {
        $filtered = array_values(array_filter($videos, function (array $v) use ($toolName, $minViews) {
            if ($v['view_count'] < $minViews || $v['duration_seconds'] < 180 || $v['duration_seconds'] > 7200) {
                return false;
            }
            if ($toolName) {
                $titleLower = mb_strtolower($v['title'] ?? '');
                $nameLower = mb_strtolower($toolName);
                if (! str_contains($titleLower, $nameLower)) {
                    return false;
                }
            }

            return true;
        }));

        if (empty($filtered)) {
            return [];
        }

        $maxViews = max(array_column($filtered, 'view_count')) ?: 1;
        $now = Carbon::now();

        $scored = [];
        foreach ($filtered as $video) {
            $viewsNorm = $video['view_count'] / $maxViews;
            $likesRatio = $video['view_count'] > 0 ? $video['like_count'] / $video['view_count'] : 0;

            $publishedAt = $video['published_at'] ? Carbon::parse($video['published_at']) : $now->copy()->subMonths(24);
            $ageMonths = min($now->diffInMonths($publishedAt), 24);
            $freshness = 1 - ($ageMonths / 24);

            $video['score'] = round(($viewsNorm * 0.40) + ($likesRatio * 0.30) + ($freshness * 0.30), 6);
            $scored[] = $video;
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, 10);
    }

    public function detectLanguage(string $title): string
    {
        $lower = mb_strtolower($title);
        if (preg_match('/tutoriel|comment\s|utiliser|français|apprendre|débutant|formation/u', $lower)) {
            return 'fr';
        }

        return 'en';
    }

    /**
     * Trouve les meilleurs tutoriels pour un outil (FR prioritaire, complété par EN).
     */
    public function findTutorials(string $toolName, int $limit = 5, ?string $toolUrl = null): array
    {
        try {
            // Passe 1 : FR (seuil 1000 vues)
            $frIds = $this->searchTutorials($toolName, 15, $toolUrl, 'fr');
            $frVideos = ! empty($frIds) ? $this->getVideoDetails($frIds) : [];
            $frResults = $this->scoreAndFilter($frVideos, $toolName, 1000);

            foreach ($frResults as &$v) {
                $v['language'] = $this->detectLanguage($v['title']);
            }
            unset($v);

            usort($frResults, fn ($a, $b) => ($b['language'] === 'fr' ? 1 : 0) <=> ($a['language'] === 'fr' ? 1 : 0) ?: ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            if (count($frResults) >= $limit) {
                return array_slice($frResults, 0, $limit);
            }

            // Passe 2 : EN pour compléter (seuil 5000 vues)
            $existingIds = array_column($frResults, 'video_id');
            $enIds = array_values(array_diff($this->searchTutorials($toolName, 10, $toolUrl, 'en'), $existingIds));
            $enVideos = ! empty($enIds) ? $this->getVideoDetails($enIds) : [];
            $enResults = $this->scoreAndFilter($enVideos, $toolName, 5000);

            foreach ($enResults as &$v) {
                $v['language'] = $this->detectLanguage($v['title']);
            }
            unset($v);

            $merged = array_merge($frResults, $enResults);
            usort($merged, fn ($a, $b) => ($b['language'] === 'fr' ? 1 : 0) <=> ($a['language'] === 'fr' ? 1 : 0) ?: ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            return array_slice($merged, 0, $limit);
        } catch (\Throwable $e) {
            Log::warning('YouTubeService::findTutorials — erreur', ['tool_name' => $toolName, 'message' => $e->getMessage()]);

            return [];
        }
    }
}
