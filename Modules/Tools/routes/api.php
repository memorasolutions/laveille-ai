<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\SavedPromptController;
use Modules\Tools\Http\Controllers\ToolsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tools', ToolsController::class)->names('tools');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/prompts', [SavedPromptController::class, 'index'])->name('api.prompts.index');
    Route::post('/prompts', [SavedPromptController::class, 'store'])->name('api.prompts.store');
    Route::delete('/prompts/{id}', [SavedPromptController::class, 'destroy'])->name('api.prompts.destroy');
});
