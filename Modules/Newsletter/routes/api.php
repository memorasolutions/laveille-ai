<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\BrevoWebhookController;

Route::middleware('api')->group(function () {
    Route::post('/webhooks/brevo', BrevoWebhookController::class)->name('webhooks.brevo');
});
