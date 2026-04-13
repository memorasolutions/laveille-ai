<?php

namespace Modules\Directory\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Facades\Log;
use MrMySQL\YoutubeTranscript\TranscriptListFetcher;

class YouTubeCaptionsService
{
    public function getTranscript(string $videoId): ?string
    {
        Log::debug("YouTubeCaptionsService: fetching transcript for {$videoId}");

        try {
            $factory = new HttpFactory;
            $fetcher = new TranscriptListFetcher(
                new Client(['verify' => false, 'timeout' => 30]),
                $factory,
                $factory,
            );

            $list = $fetcher->fetch($videoId);
            $transcript = $list->findTranscript(['fr', 'en']);
            $entries = $transcript->fetch();

            if (empty($entries)) {
                Log::debug("YouTubeCaptionsService: no entries for {$videoId}");

                return null;
            }

            $text = implode(' ', array_map(fn ($e) => $e['text'] ?? '', $entries));
            $clean = strip_tags($text);
            $clean = preg_replace('/\s+/', ' ', trim($clean));

            Log::debug("YouTubeCaptionsService: transcript ready for {$videoId}, " . strlen($clean) . ' chars');

            return $clean ?: null;
        } catch (\Throwable $e) {
            Log::debug("YouTubeCaptionsService: error for {$videoId} — {$e->getMessage()}");

            return null;
        }
    }
}
