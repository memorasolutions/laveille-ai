<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\Http\Controllers\PublicShopController;
use Modules\Shop\Http\Controllers\CartController;
use Modules\Shop\Http\Controllers\CheckoutController;
use Modules\Shop\Http\Controllers\WebhookController;
use Modules\Shop\Http\Controllers\Admin\ProductController;
use Modules\Shop\Http\Controllers\Admin\OrderController;
use Modules\Shop\Http\Controllers\Admin\SettingsController;

// Routes publiques boutique
Route::middleware('web')
    ->prefix(config('shop.routes.prefix', 'boutique'))
    ->group(function () {
        Route::get('/', [PublicShopController::class, 'index'])->name('shop.index');
        Route::get('/panier', [CartController::class, 'index'])->name('shop.cart');
        Route::post('/panier/ajouter', [CartController::class, 'add'])->name('shop.cart.add');
        Route::post('/panier/retirer', [CartController::class, 'remove'])->name('shop.cart.remove');
        Route::post('/panier/quantite', [CartController::class, 'updateQuantity'])->name('shop.cart.quantity');
        Route::post('/commander', [CheckoutController::class, 'create'])->name('shop.checkout');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'success'])->name('shop.confirmation');
        Route::get('/{product:slug}', [PublicShopController::class, 'show'])->name('shop.show');
    });

// Webhooks (pas de CSRF)
Route::middleware('api')
    ->prefix('webhooks')
    ->group(function () {
        Route::post('/stripe-shop', [WebhookController::class, 'stripe'])->name('shop.webhook.stripe');
        Route::post('/gelato', [WebhookController::class, 'gelato'])->name('shop.webhook.gelato');
    });

// Routes admin
Route::middleware(['web', 'auth'])
    ->prefix(config('shop.routes.admin_prefix', 'admin/shop'))
    ->as('admin.shop.')
    ->group(function () {
        Route::resource('products', ProductController::class)->except('show');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
