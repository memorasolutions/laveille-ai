<?php

use Illuminate\Support\Facades\Route;
use Modules\FrontTheme\Http\Controllers\FrontThemeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('frontthemes', FrontThemeController::class)->names('fronttheme');
});
