<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Academy\Http\Controllers\AcademyController;

Route::prefix('academie')->name('academy.')->group(function () {
    Route::get('/', [AcademyController::class, 'index'])->name('index');
});
