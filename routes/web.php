<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Sitemap dynamique
Route::get('/sitemap.xml', [\Modules\SEO\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Pas de frontend - redirection vers login
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Passkeys (spatie/laravel-passkeys)
Route::passkeys();

// PWA : manifest dynamique + page hors ligne (module Core)
Route::get('/manifest.webmanifest', [\Modules\Core\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/offline', [\Modules\Core\Http\Controllers\PwaController::class, 'offline'])->name('pwa.offline');

// Language switcher (module Translation)
Route::post('/locale/{locale}', \Modules\Translation\Http\Controllers\LocaleController::class)->name('locale.switch');

// Legal pages moved to Modules/Privacy
