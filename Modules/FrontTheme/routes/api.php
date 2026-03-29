<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\FrontTheme\Http\Controllers\FrontThemeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('frontthemes', FrontThemeController::class)->names('fronttheme');
});
