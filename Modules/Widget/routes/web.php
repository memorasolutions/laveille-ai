<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Widget\Http\Controllers\Admin\WidgetController;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class, 'permission:manage_widgets'])
    ->group(function () {
        Route::resource('widgets', WidgetController::class)->except(['show']);
        Route::post('widgets/reorder', [WidgetController::class, 'reorder'])->name('widgets.reorder');
    });
