<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class ScrapeMetaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url|max:2048']);

        $key = 'scrape_meta:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => __('Trop de requêtes. Réessayez dans quelques secondes.')], 429);
        }
        RateLimiter::hit($key, 60);

        $url = $request->input('url');
        $this->preventSSRF($url);

        try {
            $response = Http::timeout(10)
                ->maxRedirects(3)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; LaVeilleDeStef/1.0)'])
                ->get($url);

            if ($response->failed()) {
                return response()->json(['error' => __('Impossible de charger cette page.')], 502);
            }

            $body = $response->body();
            if (strlen($body) > 2_097_152) {
                return response()->json(['error' => __('Page trop volumineuse.')], 413);
            }

            $crawler = new Crawler($body);
            $baseUrl = $this->resolveBaseUrl($crawler, $url);

            return response()->json([
                'og_title' => $this->meta($crawler, 'meta[property="og:title"]'),
                'og_description' => $this->meta($crawler, 'meta[property="og:description"]'),
                'og_image' => $this->makeAbsolute($this->meta($crawler, 'meta[property="og:image"]'), $baseUrl),
                'description' => $this->meta($crawler, 'meta[name="description"]'),
                'favicon' => $this->makeAbsolute($this->findFavicon($crawler), $baseUrl),
                'title' => $this->extractTitle($crawler),
                'url' => $url,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            return response()->json(['error' => __('Erreur lors du scraping.')], 500);
        }
    }

    private function preventSSRF(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            throw ValidationException::withMessages(['url' => __('URL invalide.')]);
        }

        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw ValidationException::withMessages(['url' => __('Accès aux adresses privées interdit.')]);
        }
    }

    private function meta(Crawler $crawler, string $selector): ?string
    {
        $node = $crawler->filter($selector);

        return $node->count() ? trim($node->first()->attr('content') ?? '') ?: null : null;
    }

    private function extractTitle(Crawler $crawler): ?string
    {
        $node = $crawler->filter('title');

        return $node->count() ? trim($node->first()->text()) ?: null : null;
    }

    private function findFavicon(Crawler $crawler): ?string
    {
        foreach (['link[rel="icon"]', 'link[rel="shortcut icon"]', 'link[rel="apple-touch-icon"]'] as $sel) {
            $node = $crawler->filter($sel);
            if ($node->count()) {
                return $node->first()->attr('href');
            }
        }

        return '/favicon.ico';
    }

    private function resolveBaseUrl(Crawler $crawler, string $originalUrl): string
    {
        $baseNode = $crawler->filter('base');
        if ($baseNode->count() && $baseNode->first()->attr('href')) {
            return rtrim($baseNode->first()->attr('href'), '/');
        }

        $parsed = parse_url($originalUrl);

        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    }

    private function makeAbsolute(?string $path, string $baseUrl): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '//')) {
            return 'https:' . $path;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
