<?php

declare(strict_types=1);

use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::get('/health', HealthCheckResultsController::class);
