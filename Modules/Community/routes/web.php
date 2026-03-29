<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Community\Http\Controllers\Admin\ModerationController;

// Admin routes
Route::middleware(['web', 'auth'])
    ->prefix('admin/community')
    ->name('admin.community.')
    ->group(function () {
        Route::get('moderation', [ModerationController::class, 'index'])->name('moderation');
        Route::post('moderate', [ModerationController::class, 'moderate'])->name('moderate');
    });
