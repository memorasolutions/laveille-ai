<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\Http\Controllers\PublicShopController;
use Modules\Shop\Http\Controllers\CartController;
use Modules\Shop\Http\Controllers\CheckoutController;
use Modules\Shop\Http\Controllers\WebhookController;
use Modules\Shop\Http\Controllers\UserOrderController;
use Modules\Shop\Http\Controllers\ShippingQuoteController;
use Modules\Shop\Http\Controllers\OrderLookupController;
use Modules\Shop\Http\Controllers\Admin\ProductController;
use Modules\Shop\Http\Controllers\Admin\ProductWizardController;
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
        Route::get('/paiement/{order}', [CheckoutController::class, 'pay'])->name('shop.checkout.pay');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'success'])->name('shop.confirmation');
        Route::get('/suivi', [OrderLookupController::class, 'index'])->name('shop.order-lookup');
        Route::post('/suivi', [OrderLookupController::class, 'search'])->name('shop.order-lookup.search');
        Route::get('/{product:slug}', [PublicShopController::class, 'show'])->name('shop.show');
    });

// Estimation livraison (AJAX)
Route::middleware('web')
    ->post('/api/shop/shipping-quote', ShippingQuoteController::class)
    ->name('shop.shipping-quote');

// Mes commandes (authentifié)
Route::middleware(['web', 'auth'])
    ->prefix(config('shop.routes.prefix', 'boutique'))
    ->group(function () {
        Route::get('/mes-commandes', [UserOrderController::class, 'index'])->name('shop.my-orders');
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

        // Wizard création produit Gelato
        Route::get('wizard/step1', [ProductWizardController::class, 'step1'])->name('wizard.step1');
        Route::post('wizard/step1', [ProductWizardController::class, 'step1Store']);
        Route::get('wizard/step2', [ProductWizardController::class, 'step2'])->name('wizard.step2');
        Route::post('wizard/step2', [ProductWizardController::class, 'step2Store']);
        Route::get('wizard/step3', [ProductWizardController::class, 'step3'])->name('wizard.step3');
        Route::post('wizard/step3', [ProductWizardController::class, 'step3Store']);
        Route::get('wizard/step4', [ProductWizardController::class, 'step4'])->name('wizard.step4');
        Route::post('wizard/step4', [ProductWizardController::class, 'step4Store']);
        Route::get('wizard/step5', [ProductWizardController::class, 'step5'])->name('wizard.step5');
        Route::post('wizard/step5', [ProductWizardController::class, 'step5Store']);
    });
