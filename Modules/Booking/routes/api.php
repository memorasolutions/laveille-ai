<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\BookingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('bookings', BookingController::class)->names('booking');
});

// Public widget API (pas d'auth - utilisé par le widget embed)
Route::post('booking', [\Modules\Booking\Http\Controllers\Api\PublicBookingController::class, 'store'])
    ->name('api.booking.public.store');
