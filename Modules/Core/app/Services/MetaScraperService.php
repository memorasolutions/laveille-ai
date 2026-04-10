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

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36';

        $response = Http::timeout(10)
            ->maxRedirects(3)
            ->withoutVerifying()
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            ])
            ->get($url);

        if ($response->status() === 403) {
            $response = Http::timeout(10)
                ->maxRedirects(3)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => $userAgent])
                ->get($url);
        }

        if ($response->failed()) {
            throw new RuntimeException(__('Impossible de charger cette page.'));
        }

        $body = $response->body();
        if (strlen($body) > 2_097_152) {
            throw new RuntimeException(__('Page trop volumineuse.'));
        }

        $crawler = new Crawler($body);
        $baseUrl = self::resolveBaseUrl($crawler, $url);

        $ogDesc = self::meta($crawler, 'meta[property="og:description"]');
        $metaDesc = self::meta($crawler, 'meta[name="description"]');

        return [
            'og_title' => self::meta($crawler, 'meta[property="og:title"]'),
            'og_description' => $ogDesc,
            'og_image' => self::makeAbsolute(self::meta($crawler, 'meta[property="og:image"]'), $baseUrl),
            'description' => $metaDesc ?? $ogDesc ?? self::extractFirstParagraph($crawler),
            'favicon' => self::makeAbsolute(self::findFavicon($crawler), $baseUrl),
            'title' => self::extractTitle($crawler),
            'url' => $url,
        ];
    }

    private static function extractFirstParagraph(Crawler $crawler): ?string
    {
        foreach (['article', 'main', '.entry-content', '.post-content', '.content', 'body'] as $selector) {
            $nodes = $crawler->filter($selector);
            if ($nodes->count() === 0) { continue; }
            foreach ($nodes->first()->filter('p') as $p) {
                $text = trim(strip_tags($p->textContent));
                if (mb_strlen($text) < 50) { continue; }

                return mb_strlen($text) <= 300 ? $text : mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, 300), ' ')).'…';
            }
        }

        return null;
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

        return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
    }

    /**
     * Capture screenshot : og:image en priorite, OpenGraph.io en fallback.
     */
    public static function captureScreenshot(string $url, ?array $scraped = null): ?string
    {
        $data = $scraped ?? (function () use ($url) {
            try {
                return self::scrape($url);
            } catch (\Exception) {
                return [];
            }
        })();

        if (! empty($data['og_image'])) {
            return $data['og_image'];
        }

        $apiKey = env('OPENGRAPH_API_KEY');
        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(15)->withoutVerifying()->get('https://opengraph.io/api/1.1/site/'.urlencode($url), [
                'app_id' => $apiKey,
            ]);

            return $response->successful() ? $response->json('hybridGraph.image') : null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Suit les redirections HTTP pour trouver l'URL finale canonique.
     */
    public static function resolveRedirectChain(string $url): string
    {
        $currentUrl = $url;

        for ($i = 0; $i < 5; $i++) {
            try {
                $response = Http::timeout(5)
                    ->withOptions(['allow_redirects' => false])
                    ->withoutVerifying()->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36'])
                    ->head($currentUrl);

                if (! in_array($response->status(), [301, 302, 307, 308])) {
                    break;
                }

                $location = $response->header('Location');
                if (! $location) {
                    break;
                }

                $currentUrl = str_starts_with($location, 'http') ? $location : $currentUrl.$location;
            } catch (\Exception) {
                break;
            }
        }

        return $currentUrl;
    }

    /**
     * Extrait le domaine racine (sans www, sans sous-domaine).
     * Gere les TLD doubles (.co.uk, .gc.ca, .gouv.qc.ca).
     */
    public static function extractRootDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return '';
        }

        $host = strtolower(ltrim($host, 'www.'));
        $parts = explode('.', $host);

        if (count($parts) <= 2) {
            return $host;
        }

        $doubleTlds = ['co.uk', 'com.au', 'gc.ca', 'gouv.qc.ca', 'gov.uk', 'org.uk', 'net.au', 'com.br', 'co.jp'];
        $lastTwo = implode('.', array_slice($parts, -2));

        $tldParts = in_array($lastTwo, $doubleTlds) ? 3 : 2;

        return implode('.', array_slice($parts, -$tldParts));
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
            return 'https:'.$path;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }
}
