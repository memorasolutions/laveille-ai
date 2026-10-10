<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FrontTheme\Http\Controllers\HeaderNavController;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Scaffold nwidart jamais implémenté (FrontThemeController vide, aucune méthode d'écriture
// réelle) - route API supprimée par cohérence sécurité (2026-07-23), même motif que Export/
// Translation/Backup (v1.117.23).

// #3013 - Navigation de l'entête (publique, lecture seule), même source que l'entête du site.
// URL publique : GET /api/header-nav (préfixe api + nom api.header-nav posés par le RouteServiceProvider).
Route::get('/header-nav', HeaderNavController::class)
    ->middleware('throttle:60,1')
    ->name('header-nav');
