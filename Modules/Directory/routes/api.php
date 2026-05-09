<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Directory\Http\Controllers\Api\IngestController;
use Modules\Directory\Http\Controllers\Api\PublicToolsController;
use Modules\Directory\Http\Controllers\DirectoryController;

// Endpoint n8n / automation — auth par Bearer token (env DIRECTORY_INGEST_TOKEN)
Route::post('tools/ingest', IngestController::class)->name('api.tools.ingest');

// API publique JSON v1 — lecture seule, throttle 60/min/IP, S90 #43
// Préfixe 'directory/' pour éviter conflit avec Modules/Tools (outils interactifs)
Route::middleware(['throttle:60,1'])->prefix('v1/directory')->name('api.public.')->group(function () {
    Route::get('/', [PublicToolsController::class, 'index'])->name('index');
    Route::get('tools', [PublicToolsController::class, 'tools'])->name('tools');
    Route::get('tools/{slug}', [PublicToolsController::class, 'toolShow'])
        ->where('slug', '[a-z0-9-]+')
        ->name('tools.show');
    Route::get('collections', [PublicToolsController::class, 'collections'])->name('collections');
    Route::get('collections/{slug}', [PublicToolsController::class, 'collectionShow'])
        ->where('slug', '[a-z0-9-]+')
        ->name('collections.show');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('directories', DirectoryController::class)->names('directory');
});
