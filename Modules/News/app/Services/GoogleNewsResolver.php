<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class GoogleNewsResolver
{
    public static function isGoogleNewsUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        return Str::contains($host, 'news.google.com');
    }

    public function resolve(string $url): ?string
    {
        if (! self::isGoogleNewsUrl($url)) {
            return $url;
        }

        return Cache::remember('gnews_resolved:'.md5($url), 604800, function () use ($url) {
            // Tentative 1 : HTTP follow redirects
            $resolved = $this->resolveViaHttp($url);
            if ($resolved && ! self::isGoogleNewsUrl($resolved)) {
                Log::info("Google News resolved via HTTP: {$url} → {$resolved}");

                return $resolved;
            }

            // Tentative 2 : Puppeteer stealth
            $resolved = $this->resolveViaPuppeteer($url);
            if ($resolved && ! self::isGoogleNewsUrl($resolved)) {
                Log::info("Google News resolved via Puppeteer: {$url} → {$resolved}");

                return $resolved;
            }

            Log::warning("Failed to resolve Google News URL: {$url}");

            return $url;
        });
    }

    private function resolveViaHttp(string $url): ?string
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'connect_timeout' => 5,
                'allow_redirects' => ['max' => 10, 'track_redirects' => true],
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept-Language' => 'fr-CA,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Referer' => 'https://news.google.com/',
                    'Cookie' => 'CONSENT=YES+cb.20210328-17-p0.fr+FX+123',
                ],
            ]);

            $response = $client->request('GET', $url);

            // Vérifier les redirections suivies
            $redirectHistory = $response->getHeader('X-Guzzle-Redirect-History');
            if (! empty($redirectHistory)) {
                $finalUrl = end($redirectHistory);
                if (! self::isGoogleNewsUrl($finalUrl)) {
                    return $finalUrl;
                }
            }

            // Vérifier le HTML pour canonical ou meta refresh
            $html = (string) $response->getBody();

            if (preg_match('#<link[^>]*rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']#i', $html, $m)) {
                if (! self::isGoogleNewsUrl($m[1])) {
                    return $m[1];
                }
            }

            if (preg_match('#<meta[^>]*http-equiv=["\']refresh["\'][^>]*content=["\'][^"\']*url=([^"\']+)["\']#i', $html, $m)) {
                if (! self::isGoogleNewsUrl(trim($m[1]))) {
                    return trim($m[1]);
                }
            }
        } catch (\Throwable $e) {
            Log::debug("HTTP resolve failed for {$url}: {$e->getMessage()}");
        }

        return null;
    }

    private function resolveViaPuppeteer(string $url): ?string
    {
        try {
            $nodePath = env('BROWSERSHOT_NODE_PATH', '/usr/bin/node');
            $scriptPath = base_path('scripts/resolve-google-news-url.cjs');

            if (! file_exists($scriptPath)) {
                return null;
            }

            $process = Process::timeout(12)->run([$nodePath, $scriptPath, $url]);

            if ($process->successful()) {
                $resolved = trim($process->output());
                if (! empty($resolved) && filter_var($resolved, FILTER_VALIDATE_URL)) {
                    return $resolved;
                }
            }
        } catch (\Throwable $e) {
            Log::debug("Puppeteer resolve failed for {$url}: {$e->getMessage()}");
        }

        return null;
    }
}
