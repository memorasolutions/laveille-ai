<?php

declare(strict_types=1);

namespace Modules\News\Services;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ContentExtractor
{
    /**
     * Extraire le contenu propre d'un article web via Readability PHP.
     *
     * @return array{title: string, content: string, html: string, image: ?string, author: ?string, word_count: int}|null
     */
    public function extract(string $url): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept-Language' => 'fr-CA,fr;q=0.9,en;q=0.8',
                ])
                ->timeout(15)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (! $response->successful()) {
                Log::warning("ContentExtractor: HTTP {$response->status()} for {$url}");

                return null;
            }

            $html = $response->body();
            $ogImage = self::extractOgImage($html);

            // Readability PHP v3.3 : new Readability($config) puis ->parse($html)
            $config = new Configuration();
            $config->setFixRelativeURLs(true);
            $config->setOriginalURL($url);

            $readability = new Readability($config);
            $result = $readability->parse($html);

            if (! $result) {
                Log::warning("ContentExtractor: Readability failed for {$url}");

                return null;
            }

            $contentHtml = $readability->getContent() ?? '';
            $contentText = trim(strip_tags($contentHtml));
            $wordCount = str_word_count($contentText);

            if ($wordCount < 50) {
                Log::warning("ContentExtractor: too short ({$wordCount} words) for {$url}");

                return null;
            }

            return [
                'title' => $readability->getTitle() ?? '',
                'content' => $contentText,
                'html' => $contentHtml,
                'image' => $ogImage ?? $readability->getImage() ?? self::extractOgImageViaPuppeteer($url),
                'author' => $readability->getAuthor(),
                'word_count' => $wordCount,
            ];
        } catch (\Throwable $e) {
            Log::warning("ContentExtractor: exception for {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Extraire l'og:image d'un HTML.
     */
    public static function extractOgImage(string $html): ?string
    {
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+(?:property|name)=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Fallback Puppeteer : extraire og:image des sites SPA/anti-bot.
     */
    public static function extractOgImageViaPuppeteer(string $url): ?string
    {
        try {
            $nodePath = env('BROWSERSHOT_NODE_PATH', '/usr/bin/node');
            $scriptPath = base_path('scripts/extract-og-image.cjs');

            if (! file_exists($scriptPath)) {
                return null;
            }

            $process = Process::timeout(20)->run([$nodePath, $scriptPath, $url]);

            if ($process->successful()) {
                $imageUrl = trim($process->output());
                if (! empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    Log::info("ContentExtractor: Puppeteer og:image found for {$url}: {$imageUrl}");

                    return $imageUrl;
                }
            }
        } catch (\Throwable $e) {
            Log::debug("ContentExtractor: Puppeteer og:image failed for {$url}: {$e->getMessage()}");
        }

        return null;
    }
}
