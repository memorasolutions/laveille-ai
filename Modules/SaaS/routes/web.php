<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SaaS\Http\Controllers\CheckoutController;
use Modules\SaaS\Http\Controllers\StripeWebhookController;

// Stripe Webhook (pas de CSRF, pas d'auth - Stripe signe les requêtes)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// Checkout & Billing Portal (auth requise)
Route::middleware('auth')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('/billing-portal', [CheckoutController::class, 'portal'])->name('billing.portal');
});
