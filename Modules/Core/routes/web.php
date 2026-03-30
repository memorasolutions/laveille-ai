<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\FrontendModerationController;
use Modules\Core\Http\Controllers\PreviewController;

Route::get('preview/{token}', PreviewController::class)
    ->middleware('web')
    ->name('preview.show')
    ->where('token', '[a-zA-Z0-9]{64}');

// Modération frontend inline (admins uniquement)
Route::middleware(['web', 'auth'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::post('{type}/{id}/approve', [FrontendModerationController::class, 'approve'])->name('approve');
    Route::post('{type}/{id}/reject', [FrontendModerationController::class, 'reject'])->name('reject');
    Route::post('{type}/{id}/pin', [FrontendModerationController::class, 'pin'])->name('pin');
    Route::delete('{type}/{id}', [FrontendModerationController::class, 'destroy'])->name('destroy');
    Route::get('{type}/{id}/history', [FrontendModerationController::class, 'history'])->name('history');
    Route::post('ban/{userId}', [FrontendModerationController::class, 'banUser'])->name('ban');
});
