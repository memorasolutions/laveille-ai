<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Export\Http\Controllers\ExportController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('exports', ExportController::class)->names('export');
});
