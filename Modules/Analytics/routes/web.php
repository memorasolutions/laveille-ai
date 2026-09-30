<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\Admin\AnalyticsDashboardController;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

// ACTION: route déplacée hors de admin/analytics
// SELF: relocalisation < 5 lignes
// RAISON: Backoffice possède déjà admin/analytics/* (analytics INTERNE : audit, webhooks, comptages).
//         Ce module est la mesure EXTERNE GA4/GSC par page : espaces de noms séparés (admin/mesure-contenu).
// ── Écran admin (lecture seule) : même pile de middlewares que les autres écrans admin ──
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('mesure-contenu', [AnalyticsDashboardController::class, 'index'])->name('mesure_contenu.dashboard');
    });
