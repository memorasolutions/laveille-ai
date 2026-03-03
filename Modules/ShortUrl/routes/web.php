<?php

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
        Route::middleware('permission:manage_short_urls')->group(function () {
            Route::resource('short-urls', ShortUrlController::class);
            Route::post('short-urls/{short_url}/toggle', [ShortUrlController::class, 'toggleActive'])
                ->name('short-urls.toggle');
        });
    });

// ── Routes publiques de redirection ──
Route::middleware('web')->group(function () {
    Route::get('/s/{slug}', ShortUrlRedirectController::class)->name('short-url.redirect');
    Route::post('/s/{slug}/password', [ShortUrlRedirectController::class, 'checkPassword'])
        ->name('short-url.password');
});
