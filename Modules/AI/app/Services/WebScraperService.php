<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Models\KnowledgeDocument;
use Modules\AI\Models\KnowledgeUrl;
use Symfony\Component\DomCrawler\Crawler;

class WebScraperService
{
    public function __construct(
        private readonly KnowledgeBaseService $kbService
    ) {}

    public function checkRobotsTxt(string $url): bool
    {
        $parsed = parse_url($url);

        if (! isset($parsed['scheme'], $parsed['host'])) {
            return true;
        }

        $robotsUrl = $parsed['scheme'].'://'.$parsed['host'].'/robots.txt';

        try {
            $response = Http::timeout(10)->get($robotsUrl);

            if (! $response->successful()) {
                return true; // pas de robots.txt = autorisé
            }

            $path = $parsed['path'] ?? '/';
            $lines = explode("\n", $response->body());
            $inWildcard = false;
            $disallowed = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                if (str_starts_with(strtolower($line), 'user-agent:')) {
                    $agent = trim(substr($line, 11));
                    $inWildcard = ($agent === '*');
                } elseif ($inWildcard && str_starts_with(strtolower($line), 'disallow:')) {
                    $disPath = trim(substr($line, 9));
                    if ($disPath !== '') {
                        $disallowed[] = $disPath;
                    }
                }
            }

            foreach ($disallowed as $dis) {
                if (str_starts_with($path, $dis)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('WebScraper: robots.txt check failed', ['url' => $robotsUrl, 'error' => $e->getMessage()]);

            return true;
        }
    }

    public function scrapeUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'MEMORA-Bot/1.0'])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('WebScraper: HTTP error', ['url' => $url, 'status' => $response->status()]);
        } catch (\Exception $e) {
            Log::warning('WebScraper: scrape failed', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return null;
    }

    public function extractContent(string $html): string
    {
        try {
            $crawler = new Crawler($html);

            // Supprimer les éléments non pertinents
            $crawler->filter('script, style, nav, footer, header, aside, form, iframe, noscript')
                ->each(function (Crawler $node) {
                    $domNode = $node->getNode(0);
                    $domNode?->parentNode?->removeChild($domNode);
                });

            // Priorité : main > article > body
            $content = '';
            foreach (['main', 'article', '[role="main"]', '.content', '#content', 'body'] as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $content = $crawler->filter($selector)->first()->text('');
                    break;
                }
            }

            if (empty($content)) {
                $content = $crawler->text('');
            }

            // Normaliser espaces
            $content = preg_replace('/\s+/', ' ', $content);

            return trim($content);
        } catch (\Exception $e) {
            Log::warning('WebScraper: extract failed', ['error' => $e->getMessage()]);

            return strip_tags($html);
        }
    }

    public function extractTitle(string $html): string
    {
        try {
            $crawler = new Crawler($html);

            // Priorité : h1 > title > ''
            if ($crawler->filter('h1')->count() > 0) {
                return trim($crawler->filter('h1')->first()->text(''));
            }

            if ($crawler->filter('title')->count() > 0) {
                return trim($crawler->filter('title')->first()->text(''));
            }
        } catch (\Exception) {
        }

        return '';
    }

    /** @return array<string> */
    public function extractInternalLinks(string $html, string $baseUrl, int $max = 50): array
    {
        $links = [];
        $parsed = parse_url($baseUrl);
        $baseDomain = $parsed['host'] ?? '';
        $baseScheme = $parsed['scheme'] ?? 'https';

        try {
            $crawler = new Crawler($html);

            $crawler->filter('a[href]')->each(function (Crawler $node) use (&$links, $baseDomain, $baseScheme) {
                $href = trim($node->attr('href') ?? '');

                if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                    return;
                }

                // Ignorer les fichiers non-HTML
                if (preg_match('/\.(pdf|jpg|jpeg|png|gif|svg|mp4|mp3|zip|doc|docx|xls|xlsx)$/i', $href)) {
                    return;
                }

                $parsedHref = parse_url($href);

                // URL relative → absolue
                if (! isset($parsedHref['host'])) {
                    $path = $parsedHref['path'] ?? '';
                    $href = $baseScheme.'://'.$baseDomain.'/'.ltrim($path, '/');
                }

                // Même domaine seulement
                $hrefDomain = parse_url($href, PHP_URL_HOST);
                if ($hrefDomain === $baseDomain) {
                    // Supprimer fragment et query pour déduplier
                    $cleanUrl = strtok($href, '#');
                    $cleanUrl = strtok($cleanUrl, '?') ?: $cleanUrl;
                    $links[] = $cleanUrl;
                }
            });
        } catch (\Exception $e) {
            Log::warning('WebScraper: link extraction failed', ['error' => $e->getMessage()]);
        }

        return array_slice(array_unique($links), 0, $max);
    }

    public function scrapeAndIndex(KnowledgeUrl $knowledgeUrl): int
    {
        try {
            // Vérifier robots.txt
            $allowed = $this->checkRobotsTxt($knowledgeUrl->url);
            $knowledgeUrl->update(['robots_allowed' => $allowed]);

            if (! $allowed) {
                $knowledgeUrl->update(['scrape_status' => 'robots_blocked']);

                return 0;
            }

            $knowledgeUrl->update(['scrape_status' => 'scraping']);

            // Scraper page principale
            $mainHtml = $this->scrapeUrl($knowledgeUrl->url);
            if (! $mainHtml) {
                throw new \RuntimeException('Échec du scraping de la page principale');
            }

            // Supprimer les anciens documents de cette URL
            KnowledgeDocument::where('source_type', 'url')
                ->where('source_id', $knowledgeUrl->id)
                ->each(fn ($doc) => $this->kbService->deleteDocument($doc));

            // Collecter les URLs à scraper
            $internalLinks = $this->extractInternalLinks($mainHtml, $knowledgeUrl->url, $knowledgeUrl->max_pages - 1);
            $urlsToScrape = array_merge([$knowledgeUrl->url], $internalLinks);
            $urlsToScrape = array_slice(array_unique($urlsToScrape), 0, $knowledgeUrl->max_pages);

            $pagesIndexed = 0;

            foreach ($urlsToScrape as $pageUrl) {
                try {
                    // Ne pas re-scraper la page principale
                    $html = ($pageUrl === $knowledgeUrl->url) ? $mainHtml : $this->scrapeUrl($pageUrl);

                    if (! $html) {
                        continue;
                    }

                    $content = $this->extractContent($html);
                    if (mb_strlen($content) < 50) {
                        continue; // page trop vide
                    }

                    $title = $this->extractTitle($html) ?: $knowledgeUrl->label.' - Page '.($pagesIndexed + 1);

                    $this->kbService->addDocument(
                        title: $title,
                        content: $content,
                        sourceType: 'url',
                        metadata: [
                            'url' => $pageUrl,
                            'source_url_id' => $knowledgeUrl->id,
                            'hidden_source' => $knowledgeUrl->hidden_source_name,
                        ],
                        sourceId: $knowledgeUrl->id,
                    );

                    $pagesIndexed++;
                } catch (\Exception $e) {
                    Log::warning('WebScraper: page scrape failed', ['url' => $pageUrl, 'error' => $e->getMessage()]);
                }
            }

            $knowledgeUrl->update([
                'scrape_status' => 'completed',
                'pages_scraped' => $pagesIndexed,
                'last_scraped_at' => now(),
                'scrape_error' => null,
            ]);

            return $pagesIndexed;
        } catch (\Exception $e) {
            Log::error('WebScraper: scrapeAndIndex failed', ['id' => $knowledgeUrl->id, 'error' => $e->getMessage()]);

            $knowledgeUrl->update([
                'scrape_status' => 'failed',
                'scrape_error' => $e->getMessage(),
                'last_scraped_at' => now(),
            ]);

            return 0;
        }
    }
}
