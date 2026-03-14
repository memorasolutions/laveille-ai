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
        Route::get('short-urls/{short_url}', [ShortUrlController::class, 'show'])->name('short-urls.show')->middleware('permission:view_short_urls');
        Route::get('short-urls/create', [ShortUrlController::class, 'create'])->name('short-urls.create')->middleware('permission:create_short_urls');
        Route::post('short-urls', [ShortUrlController::class, 'store'])->name('short-urls.store')->middleware('permission:create_short_urls');
        Route::get('short-urls/{short_url}/edit', [ShortUrlController::class, 'edit'])->name('short-urls.edit')->middleware('permission:update_short_urls');
        Route::put('short-urls/{short_url}', [ShortUrlController::class, 'update'])->name('short-urls.update')->middleware('permission:update_short_urls');
        Route::post('short-urls/{short_url}/toggle', [ShortUrlController::class, 'toggleActive'])->name('short-urls.toggle')->middleware('permission:update_short_urls');
        Route::delete('short-urls/{short_url}', [ShortUrlController::class, 'destroy'])->name('short-urls.destroy')->middleware('permission:delete_short_urls');
    });

// ── Routes publiques de redirection ──
Route::middleware('web')->group(function () {
    Route::get('/s/{slug}', ShortUrlRedirectController::class)->name('short-url.redirect');
    Route::post('/s/{slug}/password', [ShortUrlRedirectController::class, 'checkPassword'])
        ->name('short-url.password');
});
