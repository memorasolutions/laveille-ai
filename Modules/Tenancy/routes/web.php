<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\Admin\TenantController;

Route::middleware(['web', 'auth'])
    ->prefix('admin/tenants')
    ->name('admin.tenants.')
    ->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('index')->middleware('permission:view_tenants');
        Route::get('/create', [TenantController::class, 'create'])->name('create')->middleware('permission:create_tenants');
        Route::get('/{tenant}', [TenantController::class, 'show'])->name('show')->middleware('permission:view_tenants');
        Route::post('/', [TenantController::class, 'store'])->name('store')->middleware('permission:create_tenants');
        Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->name('edit')->middleware('permission:update_tenants');
        Route::put('/{tenant}', [TenantController::class, 'update'])->name('update')->middleware('permission:update_tenants');
        Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy')->middleware('permission:delete_tenants');
    });
