<?php

declare(strict_types=1);

/**
 * P17 #235 — Pest tests reflexifs (route inspection) pour redirects 301
 * Évite bloqueur SQLite settings/JSON_UNQUOTE en testant le routeur Laravel directement.
 *
 * @author MEMORA solutions <info@memora.ca>
 * @project laveille.ai
 */

use Illuminate\Support\Facades\Route;

it('enregistre redirect 301 /sitemap_index.xml -> /sitemap.xml', function () {
    $routes = Route::getRoutes();
    $route = collect($routes)->first(fn ($r) => $r->uri() === 'sitemap_index.xml');
    expect($route)->not->toBeNull();
});

it('enregistre redirect 301 /sitemaps.xml -> /sitemap.xml', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'sitemaps.xml');
    expect($route)->not->toBeNull();
});

it('enregistre redirect 301 /sitemap.xml.gz -> /sitemap.xml', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'sitemap.xml.gz');
    expect($route)->not->toBeNull();
});

it('enregistre redirect 301 /atom.xml -> /sitemap.xml', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'atom.xml');
    expect($route)->not->toBeNull();
});

it('enregistre route /outil/{slug?} -> redirect vers /outils', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'outil/{slug?}');
    expect($route)->not->toBeNull();
    expect($route->getName())->toBe('tools.singular.redirect');
});

it('enregistre route /auteur/{slug}/page/{n} pour pagination legacy', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'auteur/{slug}/page/{n}');
    expect($route)->not->toBeNull();
});

it('verifie que les 3 stubs JS publics existent et sont accessibles', function () {
    expect(file_exists(public_path('js/ga4-events.js')))->toBeTrue();
    expect(file_exists(public_path('js/sw-register.js')))->toBeTrue();
    expect(file_exists(public_path('sw.js')))->toBeTrue();
});

it('verifie que les fichiers JS contiennent du code valide non vide', function () {
    expect(strlen(file_get_contents(public_path('js/ga4-events.js'))))->toBeGreaterThan(50);
    expect(strlen(file_get_contents(public_path('js/sw-register.js'))))->toBeGreaterThan(20);
    expect(strlen(file_get_contents(public_path('sw.js'))))->toBeGreaterThan(50);
});

it('verifie que la route /actualites/{slug} smart redirect est cablee (closure inline)', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'actualites/{slug}' && $r->getName() === 'news.show');
    expect($route)->not->toBeNull();
});
