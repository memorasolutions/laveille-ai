<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Community\Http\Controllers\Admin\ModerationController;
use Modules\Community\Http\Controllers\ReportController;

// Public routes
Route::middleware(['web', 'auth'])
    ->post('/report', [ReportController::class, 'store'])
    ->name('report.store');

// Admin routes
Route::middleware(['web', 'auth'])
    ->prefix('admin/community')
    ->name('admin.community.')
    ->group(function () {
        Route::get('moderation', [ModerationController::class, 'index'])->name('moderation');
        Route::post('moderate', [ModerationController::class, 'moderate'])->name('moderate');
    });
