<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use DOMDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorPost;

final class WebmentionSenderService
{
    public function sendForPost(AuthorPost $post): array
    {
        $externalUrls = $this->extractExternalUrls((string) $post->body_html);
        if (empty($externalUrls)) {
            return [];
        }

        $author = $post->authorProfile;
        if (! $author) {
            return [];
        }

        $sourceUrl = url("/@{$author->slug}/{$post->slug}");
        $results = [];

        foreach ($externalUrls as $url) {
            $endpoint = $this->discoverEndpoint($url);
            $results[$url] = $endpoint ? $this->send($endpoint, $sourceUrl, $url) : false;
        }

        return $results;
    }

    public function discoverEndpoint(string $url): ?string
    {
        try {
            $response = Http::timeout(5)->head($url);
            if (! $response->successful()) {
                return null;
            }

            $linkHeader = $response->header('Link');
            if (preg_match('/<([^>]+)>;\s*rel=["\']?webmention["\']?/i', (string) $linkHeader, $matches)) {
                return $this->resolveUrl($url, $matches[1]);
            }

            $html = Http::timeout(5)->get($url)->body();

            if (preg_match('/<link[^>]+rel=["\']?webmention["\']?[^>]*href=["\']([^"\']+)["\']/i', $html, $matches)) {
                return $this->resolveUrl($url, $matches[1]);
            }

            if (preg_match('/<a[^>]+rel=["\'][^"\']*\bwebmention\b[^"\']*["\'][^>]*href=["\']([^"\']+)["\']/i', $html, $matches)) {
                return $this->resolveUrl($url, $matches[1]);
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function send(string $endpoint, string $sourceUrl, string $targetUrl): bool
    {
        try {
            $response = Http::timeout(10)->asForm()->post($endpoint, [
                'source' => $sourceUrl,
                'target' => $targetUrl,
            ]);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('webmention.send.exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $success = in_array($response->status(), [200, 201, 202], true);

        Log::channel('daily')->info('webmention.send', [
            'endpoint' => $endpoint,
            'source' => $sourceUrl,
            'target' => $targetUrl,
            'status' => $response->status(),
            'success' => $success,
        ]);

        return $success;
    }

    private function resolveUrl(string $baseUrl, string $href): string
    {
        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$href}";
        }

        return rtrim($baseUrl, '/').'/'.$href;
    }

    private function extractExternalUrls(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $urls = [];
        foreach ($dom->getElementsByTagName('a') as $element) {
            $href = $element->getAttribute('href');
            if (! preg_match('/^https?:\/\//i', $href)) {
                continue;
            }

            $host = parse_url($href, PHP_URL_HOST);
            if ($host === 'laveille.ai' || str_ends_with((string) $host, '.laveille.ai')) {
                continue;
            }

            $urls[] = $href;
        }

        return array_values(array_unique($urls));
    }
}
