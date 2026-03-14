<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

// Sitemap dynamique
Route::get('/sitemap.xml', [\Modules\SEO\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Pas de frontend - redirection vers login
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Passkeys (spatie/laravel-passkeys)
Route::passkeys();

// PWA : manifest dynamique + page hors ligne
Route::get('/manifest.webmanifest', [\App\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/offline', [\App\Http\Controllers\PwaController::class, 'offline'])->name('pwa.offline');

// Language switcher
Route::post('/locale/{locale}', LocaleController::class)->name('locale.switch');

// Legal pages (public, no auth required)
Route::controller(\App\Http\Controllers\LegalController::class)->group(function () {
    Route::get('/privacy-policy', 'privacyPolicy')->name('legal.privacy');
    Route::get('/terms-of-use', 'termsOfUse')->name('legal.terms');
    Route::get('/cookie-policy', 'cookiePolicy')->name('legal.cookies');
});
