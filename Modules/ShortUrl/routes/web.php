<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\ShortUrl\Http\Controllers\ShortUrlController;
use Modules\ShortUrl\Http\Controllers\ShortUrlRedirectController;

// ── Routes admin ──
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('short-urls', [ShortUrlController::class, 'index'])->name('short-urls.index')->middleware('permission:view_short_urls');
        Route::get('short-urls/create', [ShortUrlController::class, 'create'])->name('short-urls.create')->middleware('permission:create_short_urls');
        Route::post('short-urls', [ShortUrlController::class, 'store'])->name('short-urls.store')->middleware('permission:create_short_urls');
        Route::get('short-urls/{short_url}', [ShortUrlController::class, 'show'])->name('short-urls.show')->middleware('permission:view_short_urls');
        Route::get('short-urls/{short_url}/edit', [ShortUrlController::class, 'edit'])->name('short-urls.edit')->middleware('permission:update_short_urls');
        Route::put('short-urls/{short_url}', [ShortUrlController::class, 'update'])->name('short-urls.update')->middleware('permission:update_short_urls');
        Route::post('short-urls/{short_url}/toggle', [ShortUrlController::class, 'toggleActive'])->name('short-urls.toggle')->middleware('permission:update_short_urls');
        Route::delete('short-urls/{short_url}', [ShortUrlController::class, 'destroy'])->name('short-urls.destroy')->middleware('permission:delete_short_urls');
    });

// ── Routes publiques de redirection (domaine principal) ──
Route::middleware(['web', 'throttle:60,1'])->group(function () {
    Route::get('/s/{slug}', ShortUrlRedirectController::class)->name('short-url.redirect');
    Route::post('/s/{slug}/password', [ShortUrlRedirectController::class, 'checkPassword'])
        ->middleware('throttle:5,1')
        ->name('short-url.password');
});

// ── Page publique raccourcisseur (accessible sur le site principal) ──
$frontMiddleware = \Nwidart\Modules\Facades\Module::find('FrontTheme')?->isEnabled()
    ? ['web', \Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class]
    : ['web'];

Route::middleware($frontMiddleware)
    ->prefix('raccourcir')
    ->name('shorturl.')
    ->group(function () {
        Route::get('/', [\Modules\ShortUrl\Http\Controllers\PublicShortUrlController::class, 'create'])->name('create');
        Route::post('/', [\Modules\ShortUrl\Http\Controllers\PublicShortUrlController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/{slug}/stats', [\Modules\ShortUrl\Http\Controllers\PublicShortUrlController::class, 'stats'])->name('stats');
        Route::get('/{slug}/qr', [\Modules\ShortUrl\Http\Controllers\PublicShortUrlController::class, 'qrCode'])->name('qr');
    });

// ── Espace utilisateur : mes liens courts ──
Route::middleware($frontMiddleware + ['auth'])
    ->prefix('user/liens')
    ->name('shorturl.user.')
    ->group(function () {
        Route::get('/', [\Modules\ShortUrl\Http\Controllers\UserShortUrlController::class, 'index'])->name('index');
        Route::get('/create', [\Modules\ShortUrl\Http\Controllers\UserShortUrlController::class, 'create'])->name('create');
        Route::post('/', [\Modules\ShortUrl\Http\Controllers\UserShortUrlController::class, 'store'])->name('store');
        Route::post('/scrape-meta', [\Modules\ShortUrl\Http\Controllers\UserShortUrlController::class, 'scrapeMeta'])->name('scrape-meta');
        Route::get('/{short_url}/edit', [\Modules\ShortUrl\Http\Controllers\UserShortUrlController::class, 'edit'])->name('edit');
        Route::put('/{short_url}', [\Modules\ShortUrl\Http\Controllers\UserShortUrlController::class, 'update'])->name('update');
        Route::delete('/{short_url}', [\Modules\ShortUrl\Http\Controllers\UserShortUrlController::class, 'destroy'])->name('destroy');
    });

// ── Routes domaine veille.la (redirection slug + racine → laveille.ai) ──
Route::middleware(['web'])->domain('veille.la')->group(function () {
    Route::get('/', fn () => redirect('https://laveille.ai', 301));
    Route::get('/{slug}', ShortUrlRedirectController::class)->name('short-url.veille-redirect')
        ->where('slug', '[a-zA-Z0-9\-_]+');
    Route::post('/{slug}/password', [ShortUrlRedirectController::class, 'checkPassword'])
        ->name('short-url.veille-password');
});
