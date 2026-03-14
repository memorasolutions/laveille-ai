<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\MessageFeedbackController;

Route::middleware('auth:sanctum')->prefix('ai')->name('ai.')->group(function () {
    Route::post('/messages/{message}/feedback', [MessageFeedbackController::class, 'store'])->name('messages.feedback');
});
