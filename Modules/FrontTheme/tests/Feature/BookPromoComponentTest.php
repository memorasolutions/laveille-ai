<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai — #230 remplace pub Nexus Neural par livre auteur
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->componentPath = base_path('Modules/FrontTheme/resources/views/components/book-promo.blade.php');
});

it('le composant book-promo existe sur disque', function () {
    expect($this->componentPath)->toBeFile();
});

it('le composant rend le titre du livre par défaut', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)->toContain("sans se faire poursuivre");
});

it('le composant rend le CTA vers Amazon (lien officiel)', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)->toContain('https://a.co/d/0dN4X9m2');
});

it('le CTA respecte les bonnes pratiques sécurité target=_blank + rel sponsored noopener', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)
        ->toContain('target="_blank"')
        ->toContain('rel="noopener sponsored"');
});

it('l\'image cover utilise lazy loading et decoding async pour la performance', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');
});

it('l\'image cover a un alt SEO descriptif (Loi 25, RGPD, AI Act, PME)', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)
        ->toContain('alt=')
        ->toContain('Loi 25')
        ->toContain('RGPD')
        ->toContain('AI Act');
});

it('le composant inclut un Schema.org JSON-LD Book valide', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)->toContain('<script type="application/ld+json">');

    // Extraire et valider le JSON-LD
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    expect($m[1] ?? '')->not->toBeEmpty();

    $data = json_decode($m[1], true);
    expect($data)->toBeArray()
        ->and($data['@context'] ?? null)->toBe('https://schema.org')
        ->and($data['@type'] ?? null)->toBe('Book')
        ->and($data['author']['@type'] ?? null)->toBe('Person')
        ->and($data['author']['name'] ?? null)->toBe('Stéphane Lapointe')
        ->and($data['publisher']['name'] ?? null)->toBe('MEMORA solutions')
        ->and($data['offers']['url'] ?? null)->toBe('https://a.co/d/0dN4X9m2');
});

it('le bouton CTA respecte WCAG 2.5.5 target size min-height 44px', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)->toContain('min-height:44px');
});

it('la balise picture supporte WebP + JPG fallback pour SEO et réseaux sociaux', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)
        ->toContain('<picture>')
        ->toContain('type="image/webp"')
        ->toContain('ia-sans-se-faire-poursuivre-cover-600.jpg');
});

it('aucune trace de l\'ancienne pub Nexus Neural ne subsiste dans le composant', function () {
    $html = Blade::render('<x-fronttheme::book-promo />');
    expect($html)
        ->not->toContain('Nexus')
        ->not->toContain('Marchant');
});

it('le composant accepte des props custom (title, cta_url) pour réutilisation DRY', function () {
    $html = Blade::render('<x-fronttheme::book-promo title="Mon livre" cta_url="https://example.com/x" />');
    expect($html)
        ->toContain('Mon livre')
        ->toContain('https://example.com/x');
});
