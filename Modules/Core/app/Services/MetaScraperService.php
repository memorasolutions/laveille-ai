<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class MetaScraperService
{
    public static function scrape(string $url): array
    {
        self::preventSSRF($url);

        $response = Http::timeout(10)
            ->maxRedirects(3)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; LaVeilleBot/1.0)'])
            ->get($url);

        if ($response->failed()) {
            throw new RuntimeException(__('Impossible de charger cette page.'));
        }

        $body = $response->body();
        if (strlen($body) > 2_097_152) {
            throw new RuntimeException(__('Page trop volumineuse.'));
        }

        $crawler = new Crawler($body);
        $baseUrl = self::resolveBaseUrl($crawler, $url);

        return [
            'og_title' => self::meta($crawler, 'meta[property="og:title"]'),
            'og_description' => self::meta($crawler, 'meta[property="og:description"]'),
            'og_image' => self::makeAbsolute(self::meta($crawler, 'meta[property="og:image"]'), $baseUrl),
            'description' => self::meta($crawler, 'meta[name="description"]'),
            'favicon' => self::makeAbsolute(self::findFavicon($crawler), $baseUrl),
            'title' => self::extractTitle($crawler),
            'url' => $url,
        ];
    }

    private static function preventSSRF(string $url): void
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

    private static function meta(Crawler $crawler, string $selector): ?string
    {
        $node = $crawler->filter($selector);

        return $node->count() ? trim($node->first()->attr('content') ?? '') ?: null : null;
    }

    private static function extractTitle(Crawler $crawler): ?string
    {
        $node = $crawler->filter('title');

        return $node->count() ? trim($node->first()->text()) ?: null : null;
    }

    private static function findFavicon(Crawler $crawler): ?string
    {
        foreach (['link[rel="icon"]', 'link[rel="shortcut icon"]', 'link[rel="apple-touch-icon"]'] as $sel) {
            $node = $crawler->filter($sel);
            if ($node->count() && $node->first()->attr('href')) {
                return $node->first()->attr('href');
            }
        }

        return '/favicon.ico';
    }

    private static function resolveBaseUrl(Crawler $crawler, string $originalUrl): string
    {
        $baseNode = $crawler->filter('base');
        if ($baseNode->count() && $baseNode->first()->attr('href')) {
            return rtrim($baseNode->first()->attr('href'), '/');
        }

        $parsed = parse_url($originalUrl);

        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    }

    private static function makeAbsolute(?string $path, string $baseUrl): ?string
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
