<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Directory\Http\Controllers\Api\IngestController;
use Modules\Directory\Http\Controllers\DirectoryController;

// Endpoint n8n / automation — auth par Bearer token (env DIRECTORY_INGEST_TOKEN)
Route::post('tools/ingest', IngestController::class)->name('api.tools.ingest');

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('directories', DirectoryController::class)->names('directory');
});
