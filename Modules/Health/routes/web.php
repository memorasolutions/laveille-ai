<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::get('/health', HealthCheckResultsController::class);
