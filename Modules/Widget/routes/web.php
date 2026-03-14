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
use Modules\Widget\Http\Controllers\Admin\WidgetController;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('widgets', [WidgetController::class, 'index'])->name('widgets.index')->middleware('permission:view_widgets');
        Route::get('widgets/create', [WidgetController::class, 'create'])->name('widgets.create')->middleware('permission:create_widgets');
        Route::post('widgets', [WidgetController::class, 'store'])->name('widgets.store')->middleware('permission:create_widgets');
        Route::post('widgets/reorder', [WidgetController::class, 'reorder'])->name('widgets.reorder')->middleware('permission:update_widgets');
        Route::get('widgets/{widget}/edit', [WidgetController::class, 'edit'])->name('widgets.edit')->middleware('permission:update_widgets');
        Route::put('widgets/{widget}', [WidgetController::class, 'update'])->name('widgets.update')->middleware('permission:update_widgets');
        Route::delete('widgets/{widget}', [WidgetController::class, 'destroy'])->name('widgets.destroy')->middleware('permission:delete_widgets');
    });
