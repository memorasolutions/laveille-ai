<?php

use Illuminate\Support\Facades\Route;
use Modules\Directory\Http\Controllers\DirectoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('directories', DirectoryController::class)->names('directory');
});
