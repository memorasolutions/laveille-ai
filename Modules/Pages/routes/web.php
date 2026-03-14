<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Pages\Http\Controllers\Admin\StaticPageController;

Route::prefix('admin/pages')
    ->name('admin.pages.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [StaticPageController::class, 'index'])->name('index')->middleware('permission:view_pages');
        Route::get('/{page}/preview', [StaticPageController::class, 'preview'])->name('preview')->middleware('permission:view_pages');
        Route::get('/create', [StaticPageController::class, 'create'])->name('create')->middleware('permission:create_pages');
        Route::post('/', [StaticPageController::class, 'store'])->name('store')->middleware('permission:create_pages');
        Route::get('/{page}/edit', [StaticPageController::class, 'edit'])->name('edit')->middleware('permission:update_pages');
        Route::put('/{page}', [StaticPageController::class, 'update'])->name('update')->middleware('permission:update_pages');
        Route::delete('/{page}', [StaticPageController::class, 'destroy'])->name('destroy')->middleware('permission:delete_pages');
    });
