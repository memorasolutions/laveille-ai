<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SEO\Http\Controllers\SitemapController;
use Modules\SEO\Services\SeoService;

Route::middleware('web')->group(function () {
    Route::get('/robots.txt', function () {
        return response(app(SeoService::class)->generateRobotsTxt())
            ->header('Content-Type', 'text/plain');
    })->name('robots');

    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

    // P17 #235 — redirects 301 sitemaps legacy/aliases (404s récurrents bots)
    Route::redirect('/sitemap_index.xml', '/sitemap.xml', 301);
    Route::redirect('/sitemaps.xml', '/sitemap.xml', 301);
    Route::redirect('/sitemap.xml.gz', '/sitemap.xml', 301);
    Route::redirect('/atom.xml', '/sitemap.xml', 301);
});
