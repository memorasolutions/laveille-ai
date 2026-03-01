<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\FrontSearchController;

Route::middleware(['web', 'throttle:search'])->group(function () {
    Route::get('/search', FrontSearchController::class)->name('search.front');
});
