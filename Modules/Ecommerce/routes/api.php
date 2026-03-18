<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Api\CartApiController;
use Modules\Ecommerce\Http\Controllers\Api\CheckoutApiController;
use Modules\Ecommerce\Http\Controllers\Api\OrderApiController;
use Modules\Ecommerce\Http\Controllers\Api\ProductApiController;
use Modules\Ecommerce\Http\Controllers\Api\ReviewApiController;
use Modules\Ecommerce\Http\Controllers\Api\DigitalDownloadController;
use Modules\Ecommerce\Http\Controllers\Api\WishlistApiController;

// Public
Route::prefix('ecommerce')->group(function () {
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::get('/products/{slug}', [ProductApiController::class, 'show']);

    // Reviews (public: read)
    Route::get('/products/{product}/reviews', [ReviewApiController::class, 'index']);
});

// Authenticated
Route::prefix('ecommerce')->middleware('auth:sanctum')->group(function () {
    // Cart
    Route::get('/cart', [CartApiController::class, 'index']);
    Route::post('/cart/items', [CartApiController::class, 'addItem']);
    Route::put('/cart/items/{item}', [CartApiController::class, 'updateItem']);
    Route::delete('/cart/items/{item}', [CartApiController::class, 'removeItem']);
    Route::delete('/cart', [CartApiController::class, 'clear']);

    // Checkout
    Route::post('/checkout', [CheckoutApiController::class, 'checkout']);

    // Orders
    Route::get('/orders', [OrderApiController::class, 'index']);
    Route::get('/orders/{order}', [OrderApiController::class, 'show']);

    // Wishlist
    Route::get('/wishlist', [WishlistApiController::class, 'index']);
    Route::post('/wishlist', [WishlistApiController::class, 'store']);
    Route::delete('/wishlist/{wishlist}', [WishlistApiController::class, 'destroy']);

    // Reviews (auth: write)
    Route::post('/products/{product}/reviews', [ReviewApiController::class, 'store']);

    // Digital downloads
    Route::get('/orders/{order}/downloads', [DigitalDownloadController::class, 'links']);
});

// Digital download (signed URL, no auth needed)
Route::get('/ecommerce/download/{asset}/{order}', [DigitalDownloadController::class, 'download'])
    ->name('ecommerce.download');

// Stripe webhook (no auth, verified by signature)
Route::post('/ecommerce/webhook/stripe', function () {
    $payload = request()->getContent();
    $signature = (string) request()->header('Stripe-Signature');
    app(\Modules\Ecommerce\Services\CheckoutService::class)->handleStripeWebhook($payload, $signature);

    return response()->json(['status' => 'ok']);
})->middleware('throttle:60,1');
