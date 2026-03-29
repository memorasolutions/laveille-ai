<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\News\Http\Controllers\NewsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('news', NewsController::class)->names('news');
});
