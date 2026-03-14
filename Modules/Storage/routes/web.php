<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Storage\Http\Controllers\StorageAdminController;

Route::middleware(['auth', 'permission:view_storage'])
    ->prefix('admin/storage')
    ->name('admin.storage.')
    ->group(function () {
        Route::get('/', [StorageAdminController::class, 'index'])->name('index');
        Route::get('/{disk}', [StorageAdminController::class, 'show'])->name('show');
    });
