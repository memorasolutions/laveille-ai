<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\SearchController;

Route::middleware(['throttle:search'])->prefix('v1')->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('v1.search');
});
