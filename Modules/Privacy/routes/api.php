<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Privacy\Http\Controllers\Api\ConsentController;
use Modules\Privacy\Http\Controllers\Api\RightsRequestController;

Route::post('/consent', [ConsentController::class, 'store']);
Route::get('/consent/{token}', [ConsentController::class, 'show']);
Route::get('/cookie-list', [ConsentController::class, 'cookieList']);
Route::post('/rights-request', [RightsRequestController::class, 'store']);
