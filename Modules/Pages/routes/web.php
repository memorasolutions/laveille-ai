<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Pages\Http\Controllers\Admin\StaticPageController;
use Modules\Pages\Http\Controllers\PublicPageController;

Route::prefix('admin/pages')
    ->name('admin.pages.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [StaticPageController::class, 'index'])->name('index');
        Route::get('/create', [StaticPageController::class, 'create'])->name('create');
        Route::post('/', [StaticPageController::class, 'store'])->name('store');
        Route::get('/{page}/edit', [StaticPageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [StaticPageController::class, 'update'])->name('update');
        Route::delete('/{page}', [StaticPageController::class, 'destroy'])->name('destroy');
    });

Route::prefix('pages')
    ->name('pages.')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/{slug}', [PublicPageController::class, 'show'])->name('show');
    });
