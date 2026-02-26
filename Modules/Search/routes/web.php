<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\FrontSearchController;

Route::middleware(['web', 'throttle:search'])->group(function () {
    Route::get('/search', FrontSearchController::class)->name('search.front');
});
