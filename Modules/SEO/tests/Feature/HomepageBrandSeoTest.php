<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai — P24 fix brand SEO "veille ai"
 *
 * Tests reflexifs sur JsonLdService::website() + organization() pour garantir
 * la présence des champs SEO brand critiques (alternateName, SearchAction
 * EntryPoint, sameAs LinkedIn, publisher, inLanguage). Reflexifs uniquement
 * → indépendants de la DB, lookups settings ou routes (évite bloqueurs SQLite
 * et migrations lourdes).
 */

declare(strict_types=1);

use Modules\SEO\Services\JsonLdService;

uses(Tests\TestCase::class);

test('website schema declares alternateName variants including veille ai', function () {
    $website = JsonLdService::website();

    expect($website['@type'])->toBe('WebSite');
    expect($website)->toHaveKey('alternateName');
    expect($website['alternateName'])->toBeArray();

    $variants = array_map('strtolower', $website['alternateName']);
    expect($variants)->toContain('veille ai');
    expect($variants)->toContain('veille ia québec');
});

test('website schema exposes SearchAction with EntryPoint for sitelinks', function () {
    $website = JsonLdService::website();

    expect($website)->toHaveKey('potentialAction');
    expect($website['potentialAction']['@type'])->toBe('SearchAction');
    expect($website['potentialAction']['target'])->toBeArray();
    expect($website['potentialAction']['target']['@type'])->toBe('EntryPoint');
    expect($website['potentialAction']['target']['urlTemplate'])
        ->toContain('/recherche?q={search_term_string}');
    expect($website['potentialAction']['query-input'])
        ->toBe('required name=search_term_string');
});

test('website schema declares inLanguage fr-CA and publisher organization', function () {
    $website = JsonLdService::website();

    expect($website['inLanguage'])->toBe('fr-CA');
    expect($website['publisher']['@type'])->toBe('Organization');
    expect($website['publisher'])->toHaveKey('@id');
    expect($website['publisher']['@id'])->toContain('#organization');
});

test('organization schema includes sameAs LinkedIn Stephane Lapointe', function () {
    $org = JsonLdService::organization();

    expect($org['@type'])->toBe('Organization');
    expect($org)->toHaveKey('sameAs');
    expect($org['sameAs'])->toBeArray();

    $hasLinkedin = collect($org['sameAs'])
        ->contains(fn ($url) => str_contains((string) $url, 'linkedin.com/in/lapointestephane'));
    expect($hasLinkedin)->toBeTrue();
});

test('organization schema includes alternateName brand variants and fr-CA', function () {
    $org = JsonLdService::organization();

    expect($org)->toHaveKey('alternateName');
    expect($org['alternateName'])->toBeArray();
    expect($org['inLanguage'])->toBe('fr-CA');
    expect($org['areaServed'])->toContain('CA-QC');

    $variants = array_map('strtolower', $org['alternateName']);
    expect($variants)->toContain('veille ia québec');
});

test('rendered JSON-LD contains brand keywords for crawlers', function () {
    $html = JsonLdService::render(
        JsonLdService::website(),
        JsonLdService::organization()
    );

    expect($html)->toContain('application/ld+json');
    expect($html)->toContain('"SearchAction"');
    expect($html)->toContain('"alternateName"');
    expect($html)->toContain('lapointestephane');
    expect(strtolower($html))->toContain('veille ai');
});
