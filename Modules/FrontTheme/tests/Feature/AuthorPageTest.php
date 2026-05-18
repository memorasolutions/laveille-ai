<?php

declare(strict_types=1);

/*
 * Tests reflexifs page auteur EEAT 2026 (#234 S95).
 * View render direct (no HTTP) — évite dépendance DB lourde / route binding.
 * Cible : présence éléments EEAT clés (H1, Schema.org Person valide,
 *         rel=me LinkedIn, rel=author meta, photo lazy, livre, qualifications).
 */

use Illuminate\Support\Facades\Blade;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->viewPath = base_path('Modules/FrontTheme/resources/views/author/show.blade.php');
    $this->controllerPath = base_path('Modules/FrontTheme/app/Http/Controllers/AuthorController.php');
});

it('le fichier vue author/show.blade.php existe', function () {
    expect($this->viewPath)->toBeFile();
});

it('le controller AuthorController.php existe', function () {
    expect($this->controllerPath)->toBeFile();
});

it('les 5 photos auteur sont présentes dans public/images/author/', function () {
    $base = public_path('images/author');
    expect(file_exists("$base/stephane-lapointe-80.webp"))->toBeTrue();
    expect(file_exists("$base/stephane-lapointe-80.jpg"))->toBeTrue();
    expect(file_exists("$base/stephane-lapointe-256.webp"))->toBeTrue();
    expect(file_exists("$base/stephane-lapointe-256.jpg"))->toBeTrue();
    expect(file_exists("$base/stephane-lapointe-schema.jpg"))->toBeTrue();
});

it('la vue contient H1 avec nom + rôle (EEAT signal expertise)', function () {
    $html = file_get_contents($this->viewPath);
    expect($html)
        ->toContain('<h1')
        ->toContain('$author[\'name\']')
        ->toContain('$author[\'role\']');
});

it('la vue utilise picture webp + jpg fallback avec lazy loading', function () {
    $html = file_get_contents($this->viewPath);
    expect($html)
        ->toContain('<picture')
        ->toContain('type="image/webp"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');
});

it('la vue inclut rel="me" sur lien LinkedIn (signal IndieWeb EEAT 2026)', function () {
    $html = file_get_contents($this->viewPath);
    expect($html)->toContain('rel="me noopener noreferrer"');
});

it('la vue pousse <meta name="author"> et <link rel="me"> dans head', function () {
    $html = file_get_contents($this->viewPath);
    expect($html)
        ->toContain('<meta name="author"')
        ->toContain('<link rel="me"');
});

it('la vue injecte Schema.org JSON-LD via $schemaJson', function () {
    $html = file_get_contents($this->viewPath);
    expect($html)
        ->toContain('application/ld+json')
        ->toContain('$schemaJson');
});

it('la vue réutilise le composant <x-fronttheme::book-promo> (DRY)', function () {
    $html = file_get_contents($this->viewPath);
    expect($html)->toContain('<x-fronttheme::book-promo');
});

it('le controller pré-encode Schema.org Person avec sameAs + jobTitle + knowsAbout', function () {
    $php = file_get_contents($this->controllerPath);
    expect($php)
        ->toContain("'@type' => 'Person'")
        ->toContain("'sameAs'")
        ->toContain("'jobTitle'")
        ->toContain("'knowsAbout'")
        ->toContain("'worksFor'");
});

it('le controller utilise image dédiée auteur (fallback logo-avatar)', function () {
    $php = file_get_contents($this->controllerPath);
    expect($php)
        ->toContain('stephane-lapointe-schema.jpg')
        ->toContain('file_exists');
});

it('le partial section-author-byline utilise photo dédiée stephane-lapointe-80', function () {
    $partialPath = base_path('Modules/FrontTheme/resources/views/partials/section-author-byline.blade.php');
    $html = file_get_contents($partialPath);
    expect($html)
        ->toContain('stephane-lapointe-80.webp')
        ->toContain('stephane-lapointe-80.jpg')
        ->toContain('rel="author"');
});

it('le master layout pousse link rel=author vers page auteur', function () {
    $masterPath = base_path('Modules/FrontTheme/resources/views/layouts/master.blade.php');
    $html = file_get_contents($masterPath);
    expect($html)
        ->toContain('rel="author"')
        ->toContain("author.show', 'stephane-lapointe'");
});

it('le sitemap inclut la page auteur (EEAT discoverability)', function () {
    $sitemapPath = base_path('Modules/SEO/app/Http/Controllers/SitemapController.php');
    $php = file_get_contents($sitemapPath);
    expect($php)
        ->toContain("Route::has('author.show')")
        ->toContain("author.show', \$slug");
});
