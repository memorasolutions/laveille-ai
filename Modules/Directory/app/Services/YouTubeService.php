<?php

namespace Modules\Directory\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    public function findTutorials(string $toolName, int $limit = 5): array
    {
        try {
            $videoIds = $this->searchTutorials($toolName);

            if (empty($videoIds)) {
                return [];
            }

            $videos = $this->getVideoDetails($videoIds);

            if (empty($videos)) {
                return [];
            }

            return array_slice($this->scoreAndFilter($videos), 0, $limit);
        } catch (\Throwable $e) {
            Log::warning('YouTubeService::findTutorials — erreur', [
                'tool_name' => $toolName,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function searchTutorials(string $toolName, int $maxResults = 10): array
    {
        try {
            $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
                'key' => config('directory.youtube_api_key'),
                'q' => "{$toolName} tutoriel français",
                'part' => 'id',
                'type' => 'video',
                'order' => 'viewCount',
                'relevanceLanguage' => 'fr',
                'regionCode' => 'CA',
                'videoDuration' => 'medium',
                'publishedAfter' => Carbon::now()->subMonths(24)->toIso8601String(),
                'maxResults' => $maxResults,
            ]);

            if ($response->failed()) {
                Log::warning('YouTubeService::searchTutorials — échec API', [
                    'tool_name' => $toolName,
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
                Log::warning('YouTubeService::getVideoDetails — échec API', [
                    'status' => $response->status(),
                ]);

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
            Log::warning('YouTubeService::getVideoDetails — exception', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function scoreAndFilter(array $videos): array
    {
        $filtered = array_values(array_filter($videos, fn (array $v) => $v['view_count'] >= 5000
            && $v['duration_seconds'] >= 180
            && $v['duration_seconds'] <= 7200
        ));

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

        return array_slice($scored, 0, 5);
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
}
