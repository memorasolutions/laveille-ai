<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\FrontSearchController;

Route::middleware(['web'])->group(function () {
    Route::get('/recherche', [FrontSearchController::class, 'index'])->name('search.index');
});
