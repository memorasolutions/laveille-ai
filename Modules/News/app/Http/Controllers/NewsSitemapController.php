<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * R6 v1.6.0 — Google News Sitemap dédié (best practice mai 2026 : Top Stories eligibility).
 *  - Articles publiés < 72h uniquement (fenêtre Google News).
 *  - Max 1000 URLs (cap Google).
 *  - Namespace <news:news> avec publication, language, publication_date, title.
 *  - Cache 5 min (réduit charge DB sur crawler hits).
 */

namespace Modules\News\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\News\Models\NewsArticle;

class NewsSitemapController
{
    private const MAX_URLS = 1000;

    private const FRESHNESS_HOURS = 72;

    private const CACHE_TTL_SECONDS = 300;

    public function index(): Response
    {
        $xml = Cache::remember('news_sitemap_xml_v1', self::CACHE_TTL_SECONDS, function (): string {
            $articles = NewsArticle::query()
                ->where('pub_date', '>=', now()->subHours(self::FRESHNESS_HOURS))
                ->where('seo_status', 'index') // exclut les actualités élaguées (noindex/gone)
                // ACTION : chantier AdSense « faible valeur » (2026-08-18) - cette requête
                // filtre directement sur la table (jamais NewsArticle::published()), donc
                // l'override de scopePublished() ne la couvre PAS : filtre explicite requis.
                // MCP: SELF (<5 lignes)
                // RAISON: une fiche retirée (retired_at non nul, réponse 410) ne doit jamais
                // apparaître dans le sitemap Google News.
                ->whereNull('retired_at')
                ->whereNotNull('slug')
                ->orderByDesc('pub_date')
                ->limit(self::MAX_URLS)
                ->get(['id', 'slug', 'title', 'seo_title', 'pub_date', 'updated_at', 'category_tag']);

            return $this->buildXml($articles, (string) config('app.name'));
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function buildXml(\Illuminate\Support\Collection $articles, string $publicationName): string
    {
        $entries = $articles->map(function (NewsArticle $a) use ($publicationName) {
            $loc = htmlspecialchars(route('news.show', $a), ENT_XML1 | ENT_QUOTES);
            $title = htmlspecialchars(trim($a->seo_title ?? $a->title ?? ''), ENT_XML1 | ENT_QUOTES);
            $pubDate = $a->pub_date?->toIso8601String() ?? now()->toIso8601String();
            $publication = htmlspecialchars($publicationName, ENT_XML1 | ENT_QUOTES);
            $genres = $a->category_tag ? '<news:genres>' . htmlspecialchars((string) $a->category_tag, ENT_XML1 | ENT_QUOTES) . '</news:genres>' : '';

            return <<<XML
  <url>
    <loc>{$loc}</loc>
    <news:news>
      <news:publication>
        <news:name>{$publication}</news:name>
        <news:language>fr</news:language>
      </news:publication>
      <news:publication_date>{$pubDate}</news:publication_date>
      <news:title>{$title}</news:title>
      {$genres}
    </news:news>
  </url>
XML;
        })->implode("\n");

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
{$entries}
</urlset>
XML;
    }
}
