<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\EmailPreviewController;

Route::middleware(['auth', 'permission:manage_settings'])
    ->prefix('admin/email-preview')
    ->name('admin.email-preview.')
    ->group(function () {
        Route::get('/', [EmailPreviewController::class, 'index'])->name('index');
        Route::get('/{type}', [EmailPreviewController::class, 'preview'])->name('show');
    });
