<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Community\Http\Controllers\Admin\ModerationController;
use Modules\Community\Http\Controllers\CategorySubscriptionController;
use Modules\Community\Http\Controllers\ReportController;

// Public routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');
    Route::post('/category-subscription/toggle', [CategorySubscriptionController::class, 'toggle'])->name('category-subscription.toggle');
});

// Admin routes
Route::middleware(['web', 'auth'])
    ->prefix('admin/community')
    ->name('admin.community.')
    ->group(function () {
        Route::get('moderation', [ModerationController::class, 'index'])->name('moderation');
        Route::post('moderate', [ModerationController::class, 'moderate'])->name('moderate');
    });
