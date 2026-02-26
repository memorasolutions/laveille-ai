<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Editor\Http\Controllers\EditorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('editors', EditorController::class)->names('editor');
});
