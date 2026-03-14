<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Health\Http\Controllers\Admin\IncidentController;
use Modules\Health\Http\Controllers\StatusPageController;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::get('/health', HealthCheckResultsController::class);
Route::get('/status', [StatusPageController::class, 'index'])->name('status.index');

Route::middleware(['auth', 'verified'])
    ->prefix('admin/health')
    ->name('admin.health.')
    ->group(function () {
        Route::resource('incidents', IncidentController::class)
            ->middleware('permission:manage_incidents')
            ->except(['show']);
    });
