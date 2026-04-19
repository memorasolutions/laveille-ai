<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\CloudflareCache\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudflareCacheService
{
    public function isConfigured(): bool
    {
        return ! empty(config('cloudflarecache.api_token')) && ! empty(config('cloudflarecache.zone_id'));
    }

    public function purgeByUrls(array $urls): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('CloudflareCacheService: not configured (missing CLOUDFLARE_API_TOKEN or CLOUDFLARE_ZONE_ID)');

            return false;
        }

        if (empty($urls)) {
            return true;
        }

        $urls = array_values(array_unique($urls));
        $zoneId = config('cloudflarecache.zone_id');
        $token = config('cloudflarecache.api_token');
        $timeout = config('cloudflarecache.timeout', 5);
        $chunks = array_chunk($urls, 30);

        foreach ($chunks as $chunk) {
            $response = Http::timeout($timeout)
                ->withToken($token)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'files' => $chunk,
                ]);

            $success = $response->json('success') === true;

            if ($success) {
                Log::info('CloudflareCacheService: successfully purged URLs', ['count' => count($chunk)]);
            } else {
                $body = $response->body();
                $truncatedBody = Str::limit($body, 500);
                Log::warning('CloudflareCacheService: failed to purge URLs', [
                    'count' => count($chunk),
                    'response' => $truncatedBody,
                ]);

                return false;
            }
        }

        return true;
    }

    public function purgeEverything(): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('CloudflareCacheService: not configured (missing CLOUDFLARE_API_TOKEN or CLOUDFLARE_ZONE_ID)');

            return false;
        }

        $zoneId = config('cloudflarecache.zone_id');
        $token = config('cloudflarecache.api_token');
        $timeout = config('cloudflarecache.timeout', 5);

        $response = Http::timeout($timeout)
            ->withToken($token)
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                'purge_everything' => true,
            ]);

        $success = $response->json('success') === true;

        if ($success) {
            Log::info('CloudflareCacheService: successfully purged everything');

            return true;
        }

        $body = $response->body();
        $truncatedBody = Str::limit($body, 500);
        Log::warning('CloudflareCacheService: failed to purge everything', [
            'response' => $truncatedBody,
        ]);

        return false;
    }
}
