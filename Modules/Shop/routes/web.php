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
use Modules\Shop\Http\Middleware\ShopMaintenanceMode;

// Routes publiques boutique — protégées par ShopMaintenanceMode (kill switch admin only quand SHOP_MAINTENANCE=true)
Route::middleware(['web', ShopMaintenanceMode::class])
    ->prefix(config('shop.routes.prefix', 'boutique'))
    ->group(function () {
        Route::get('/', [PublicShopController::class, 'index'])->name('shop.index');
        Route::get('/panier', [CartController::class, 'index'])->name('shop.cart');
        Route::post('/panier/ajouter', [CartController::class, 'add'])->name('shop.cart.add');
        Route::post('/panier/retirer', [CartController::class, 'remove'])->name('shop.cart.remove');
        Route::post('/panier/quantite', [CartController::class, 'updateQuantity'])->name('shop.cart.quantity');
        Route::post('/panier/variante', [CartController::class, 'updateVariant'])->name('shop.cart.variant');
        Route::post('/commander', [CheckoutController::class, 'create'])->name('shop.checkout');
        Route::get('/paiement/{order}', [CheckoutController::class, 'pay'])->name('shop.checkout.pay');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'success'])->name('shop.confirmation');
        Route::get('/suivi', [OrderLookupController::class, 'index'])->name('shop.order-lookup');
        Route::post('/suivi', [OrderLookupController::class, 'search'])->name('shop.order-lookup.search');
        Route::get('/mes-commandes', [UserOrderController::class, 'index'])->name('shop.my-orders')->middleware('auth');
        Route::get('/{product:slug}', [PublicShopController::class, 'show'])->name('shop.show');
    });

// Estimation livraison (AJAX) — protégée aussi (évite checkout via API pendant maintenance)
Route::middleware(['web', ShopMaintenanceMode::class])
    ->post('/api/shop/shipping-quote', ShippingQuoteController::class)
    ->name('shop.shipping-quote');

// Webhooks (pas de CSRF)
Route::middleware('api')
    ->prefix('webhooks')
    ->group(function () {
        Route::post('/stripe-shop', [WebhookController::class, 'stripe'])->name('shop.webhook.stripe');
        Route::post('/gelato', [WebhookController::class, 'gelato'])->name('shop.webhook.gelato');
    });

// Routes admin
// SÉCURITÉ (2026-07-23) : ce groupe n'était protégé QUE par ['web','auth'] - n'importe quel
// utilisateur connecté (même un simple client) pouvait gérer les produits/commandes/réglages.
// Trouvé par la passe adversariale /100 round 6 (préfixe construit via config(), invisible au
// grep littéral prefix('admin des rounds précédents). Permissions view_/create_/update_/
// delete_products et view_/update_ecommerce_orders réutilisées : elles existaient déjà dans le
// seeder RBAC mais n'avaient jamais été câblées à aucune route.
Route::middleware(['web', 'auth'])
    ->prefix(config('shop.routes.admin_prefix', 'admin/shop'))
    ->as('admin.shop.')
    ->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index')->middleware('permission:view_products');
        Route::middleware('permission:create_products')->group(function () {
            Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
        });
        Route::middleware('permission:update_products')->group(function () {
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::patch('products/{product}', [ProductController::class, 'update']);
        });
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->middleware('permission:delete_products');

        Route::middleware('permission:view_ecommerce_orders')->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        });
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel')->middleware('permission:update_ecommerce_orders');

        Route::middleware('permission:view_shop')->group(function () {
            Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        });
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('permission:manage_shop');

        // Wizard création produit Gelato
        Route::middleware('permission:create_products')->group(function () {
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
    });
