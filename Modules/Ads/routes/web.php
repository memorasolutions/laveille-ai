<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Ads\Http\Controllers\AdPlacementController;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

Route::prefix('admin/ads')
    ->name('admin.ads.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [AdPlacementController::class, 'index'])->name('index');
        Route::get('/create', [AdPlacementController::class, 'create'])->name('create');
        Route::post('/', [AdPlacementController::class, 'store'])->name('store');
        Route::get('/{ad}/edit', [AdPlacementController::class, 'edit'])->name('edit');
        Route::put('/{ad}', [AdPlacementController::class, 'update'])->name('update');
        Route::delete('/{ad}', [AdPlacementController::class, 'destroy'])->name('destroy');
    });
