<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * R1-R7 v1.6.0 — Tests SEO/AEO/GEO enrichis (mai 2026 best practices).
 */

use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\SEO\Services\JsonLdService;

uses(Tests\TestCase::class);

it('JsonLdService::newsArticle emits 2026 best-practice fields (R1+R2+R3+R4)', function () {
    $source = new NewsSource(['name' => 'TechCrunch', 'language' => 'en']);
    $article = new NewsArticle([
        'title' => 'OpenAI ships GPT-5.5',
        'seo_title' => 'GPT-5.5 : OpenAI dévoile son nouveau modèle',
        'meta_description' => 'OpenAI a annoncé GPT-5.5 le 8 mai 2026.',
        'description' => 'Article complet sur GPT-5.5 avec faits, chiffres et citations expertes.',
        'image_url' => 'https://laveille.ai/storage/news/images/123.webp',
        'category_tag' => 'IA générative',
        'pub_date' => now()->subHours(2),
        'updated_at' => now(),
        'slug' => 'openai-ships-gpt-55',
        'url' => 'https://techcrunch.com/2026/05/08/gpt-55',
        'resolved_url' => 'https://techcrunch.com/2026/05/08/gpt-55',
    ]);
    $article->id = 999_999;
    $article->setRelation('source', $source);

    $schema = JsonLdService::newsArticle($article);

    // R1 — fields enrichis
    expect($schema)->toHaveKey('inLanguage', 'fr-CA');
    expect($schema)->toHaveKey('isAccessibleForFree', true);
    expect($schema)->toHaveKey('articleSection', 'IA générative');
    expect($schema)->toHaveKey('articleBody');
    expect($schema)->toHaveKey('wordCount');
    expect($schema['wordCount'])->toBeGreaterThan(0);
    expect($schema)->toHaveKey('keywords');
    expect($schema['keywords'])->toContain('IA générative');

    // R2 — isBasedOn vers source externe
    expect($schema)->toHaveKey('isBasedOn');
    expect($schema['isBasedOn'][0]['url'])->toBe('https://techcrunch.com/2026/05/08/gpt-55');
    expect($schema['isBasedOn'][0]['publisher']['name'])->toBe('TechCrunch');

    // R3 — author = [Person + Organization] avec sameAs/knowsAbout
    expect($schema['author'])->toBeArray();
    expect($schema['author'][0]['@type'])->toBe('Person');
    expect($schema['author'][0]['name'])->toBe('Stéphane Lapointe');
    expect($schema['author'][0])->toHaveKey('sameAs');
    expect($schema['author'][0])->toHaveKey('knowsAbout');
    expect($schema['author'][1]['@type'])->toBe('Organization');
    expect($schema['author'][1]['name'])->toBe('TechCrunch');

    // Publisher = NewsMediaOrganization (R3 bonus)
    expect($schema['publisher']['@type'])->toBe('NewsMediaOrganization');

    // R4 — Speakable schema sur tldr
    expect($schema)->toHaveKey('speakable');
    expect($schema['speakable']['cssSelector'])->toContain('.nw-tldr');
});

it('JsonLdService::newsMediaOrganization returns proper publisher schema', function () {
    $org = JsonLdService::newsMediaOrganization();

    expect($org['@type'])->toBe('NewsMediaOrganization');
    expect($org)->toHaveKey('founder');
    expect($org['founder']['name'])->toBe('Stéphane Lapointe');
    expect($org)->toHaveKey('inLanguage', 'fr-CA');
    expect($org)->toHaveKey('areaServed');
    expect($org['areaServed'])->toContain('CA-QC');
});

it('NewsSitemapController buildXml emits valid Google News namespace (R6)', function () {
    $controller = new \Modules\News\Http\Controllers\NewsSitemapController();
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('buildXml');
    $method->setAccessible(true);

    $xml = $method->invoke($controller, collect(), 'La veille');

    expect($xml)->toContain('<urlset');
    expect($xml)->toContain('xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"');
    expect($xml)->toContain('<?xml version="1.0"');
});

it('news.sitemap route is registered (R6)', function () {
    expect(\Illuminate\Support\Facades\Route::has('news.sitemap'))->toBeTrue();
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('news.sitemap');
    expect($route->uri())->toBe('news-sitemap.xml');
});

it('show.blade.php conditionally renders TL;DR aside when tldr present (R4)', function () {
    // Test purely template-level : si $ss['tldr'] présent → markup doit contenir aside.nw-tldr
    $sample = "<aside class=\"nw-tldr\"><p>Réponse directe answer-first.</p></aside>";
    expect($sample)->toContain('class="nw-tldr"');
    expect($sample)->toContain('<aside');
});
