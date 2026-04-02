<?php

declare(strict_types=1);

namespace Modules\SEO\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    public static function submit(string $url): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        $response = Http::timeout(10)->post('https://api.indexnow.org/indexnow', [
            'host' => parse_url(config('app.url'), PHP_URL_HOST),
            'key' => self::getKey(),
            'keyLocation' => config('app.url').'/'.self::getKey().'.txt',
            'urlList' => [$url],
        ]);

        if (! $response->successful()) {
            Log::warning('IndexNow submission failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public static function submitBatch(array $urls): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        $response = Http::timeout(10)->post('https://api.indexnow.org/indexnow', [
            'host' => parse_url(config('app.url'), PHP_URL_HOST),
            'key' => self::getKey(),
            'keyLocation' => config('app.url').'/'.self::getKey().'.txt',
            'urlList' => $urls,
        ]);

        if (! $response->successful()) {
            Log::warning('IndexNow batch submission failed', [
                'urls' => $urls,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public static function getKey(): string
    {
        return env('INDEXNOW_KEY', 'b79927568427fb2c3fe6a1c410f2c35b');
    }

    public static function isEnabled(): bool
    {
        return (bool) env('INDEXNOW_ENABLED', false);
    }
}
