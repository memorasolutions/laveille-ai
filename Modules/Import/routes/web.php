<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Import\Http\Controllers\ImportController;

Route::prefix('admin')->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('import', [ImportController::class, 'index'])->name('import.index')->middleware('permission:manage_imports');
        Route::post('import/preview', [ImportController::class, 'preview'])->name('import.preview')->middleware('permission:manage_imports');
        Route::post('import/execute', [ImportController::class, 'execute'])->name('import.execute')->middleware('permission:manage_imports');
        Route::get('import/template/{type}', [ImportController::class, 'template'])->name('import.template')->middleware('permission:manage_imports');
    });
