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
    // Secret dans l'URL (convention déjà utilisée par ai/webhooks/email/{secret}) = auth simple compatible Brevo.
    Route::post('/webhooks/brevo/{secret}', BrevoWebhookController::class)->name('webhooks.brevo');
});
