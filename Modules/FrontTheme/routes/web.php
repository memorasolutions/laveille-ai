<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FrontTheme\Http\Controllers\ContactController;
use Modules\FrontTheme\Http\Controllers\HomeController;
use Modules\FrontTheme\Http\Controllers\ResourceHubController;
use Modules\FrontTheme\Http\Middleware\SetFrontendTheme;

Route::middleware(['web', SetFrontendTheme::class])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home')->middleware('cacheResponse:600');
    Route::get('/ressources', [ResourceHubController::class, 'index'])->name('resources.index')->middleware('cacheResponse:600');
    Route::get('/plan-du-site', [\Modules\FrontTheme\Http\Controllers\SitemapHtmlController::class, 'index'])->name('sitemap.html')->middleware('cacheResponse:3600');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');

    // Page auteur EEAT 2026 NN/g (#218 S84 — Schema.org Person + sameAs LinkedIn)
    Route::get('/auteur/{slug}', [\Modules\FrontTheme\Http\Controllers\AuthorController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('author.show');

    Route::get('/lien-expire', function () {
        $reason = request('reason', 'notfound');
        return response()->view('fronttheme::link-expired', compact('reason'), $reason === 'expired' ? 410 : 404);
    })->name('link.expired');
    Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,1')->name('contact.send');

    // Redirections legacy WordPress (SEO 301)
    Route::get('/{year}/{month}/{day}/{slug}', function (string $year, string $month, string $day, string $slug) {
        return redirect()->route('blog.show', $slug, 301);
    })->where(['year' => '\d{4}', 'month' => '\d{2}', 'day' => '\d{2}']);

    Route::get('/les-articles', fn () => redirect()->route('blog.index', [], 301));
    Route::get('/les-articles/{slug}', fn (string $slug) => redirect()->route('blog.index', [], 301));
    Route::get('/category/{slug}', fn (string $slug) => redirect()->route('blog.category', $slug, 301));

    // Redirections WordPress supplémentaires (migration SEO)
    // /feed desactive (session 2026-04-04b) — redirect 301 vers accueil
    Route::get('/feed', fn () => redirect('/', 301));
    Route::get('/feed/{any}', fn () => redirect('/', 301))->where('any', '.*');
    Route::get('/les-outils', fn () => redirect('/outils', 301));
    Route::get('/le-concentre', fn () => redirect('/categorie/le-concentre', 301));
    Route::get('/wp-admin/{any?}', fn () => redirect('/', 301))->where('any', '.*');
    Route::get('/wp-json/{any?}', fn () => redirect('/', 301))->where('any', '.*');
    Route::get('/wp-content/{any?}', fn () => redirect('/', 301))->where('any', '.*');
    Route::get('/wp-login.php', fn () => redirect('/', 301));
    Route::get('/xmlrpc.php', fn () => abort(410));
});
