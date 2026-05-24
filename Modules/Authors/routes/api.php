<?php

use Illuminate\Support\Facades\Route;
use Modules\Authors\Http\Controllers\AuthorsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authors', AuthorsController::class)->names('authors');
});
