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

// Page d'accueil : gérée par le module FrontTheme si actif, sinon fallback login
if (! \Nwidart\Modules\Facades\Module::find('FrontTheme')?->isEnabled()) {
    Route::get('/', function () {
        return redirect()->route('login');
    })->name('home');
}

// Passkeys (spatie/laravel-passkeys)
Route::passkeys();

// TEMP: config clear + git status (supprimer après usage)
Route::get('/tmp-deploy-check-x9k', function () {
    \Artisan::call('config:clear');
    \Artisan::call('view:clear');
    \Artisan::call('route:clear');
    $git = shell_exec('cd ' . base_path() . ' && git log --oneline -3 2>&1');
    return response()->json(['config' => 'cleared', 'git' => $git]);
});

// PWA : manifest dynamique + page hors ligne (module Core)
Route::get('/manifest.webmanifest', [\Modules\Core\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/offline', [\Modules\Core\Http\Controllers\PwaController::class, 'offline'])->name('pwa.offline');

// Language switcher (module Translation)
Route::match(['get', 'post'], '/locale/{locale}', \Modules\Translation\Http\Controllers\LocaleController::class)->name('locale.switch');

// Legal pages moved to Modules/Privacy
