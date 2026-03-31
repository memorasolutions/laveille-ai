<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\SavedPromptController;
use Modules\Tools\Http\Controllers\SavedDrawPresetController;
use Modules\Tools\Http\Controllers\SavedQrPresetController;
use Modules\Tools\Http\Controllers\SavedTeamPresetController;
use Modules\Tools\Http\Controllers\ToolsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tools', ToolsController::class)->names('tools');
});

Route::middleware(['web', 'auth', 'throttle:60,1'])->group(function () {
    Route::get('/prompts', [SavedPromptController::class, 'index'])->name('api.prompts.index');
    Route::post('/prompts', [SavedPromptController::class, 'store'])->name('api.prompts.store');
    Route::put('/prompts/{id}', [SavedPromptController::class, 'update'])->name('api.prompts.update');
    Route::delete('/prompts/{id}', [SavedPromptController::class, 'destroy'])->name('api.prompts.destroy');

    Route::get('/team-presets', [SavedTeamPresetController::class, 'index'])->name('api.team-presets.index');
    Route::post('/team-presets', [SavedTeamPresetController::class, 'store'])->name('api.team-presets.store');
    Route::put('/team-presets/{id}', [SavedTeamPresetController::class, 'update'])->name('api.team-presets.update');
    Route::delete('/team-presets/{id}', [SavedTeamPresetController::class, 'destroy'])->name('api.team-presets.destroy');

    Route::get('/draw-presets', [SavedDrawPresetController::class, 'index'])->name('api.draw-presets.index');
    Route::post('/draw-presets', [SavedDrawPresetController::class, 'store'])->name('api.draw-presets.store');
    Route::put('/draw-presets/{id}', [SavedDrawPresetController::class, 'update'])->name('api.draw-presets.update');
    Route::delete('/draw-presets/{id}', [SavedDrawPresetController::class, 'destroy'])->name('api.draw-presets.destroy');

    Route::get('/qr-presets', [SavedQrPresetController::class, 'index'])->name('api.qr-presets.index');
    Route::post('/qr-presets', [SavedQrPresetController::class, 'store'])->name('api.qr-presets.store');
    Route::put('/qr-presets/{id}', [SavedQrPresetController::class, 'update'])->name('api.qr-presets.update');
    Route::delete('/qr-presets/{id}', [SavedQrPresetController::class, 'destroy'])->name('api.qr-presets.destroy');
});
