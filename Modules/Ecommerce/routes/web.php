<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Ecommerce\Http\Controllers\Admin\CategoryController;
use Modules\Ecommerce\Http\Controllers\Admin\CouponController;
use Modules\Ecommerce\Http\Controllers\Admin\DashboardController;
use Modules\Ecommerce\Http\Controllers\Admin\OrderController;
use Modules\Ecommerce\Http\Controllers\Admin\ProductController;

Route::prefix('admin/ecommerce')
    ->name('admin.ecommerce.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('permission:view_ecommerce');

        // Products
        Route::middleware('permission:view_products')->group(function () {
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
        });

        Route::middleware('permission:create_products')->group(function () {
            Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
        });

        Route::middleware('permission:update_products')->group(function () {
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        });

        Route::delete('products/{product}', [ProductController::class, 'destroy'])
            ->name('products.destroy')
            ->middleware('permission:delete_products');

        // Categories
        Route::middleware('permission:view_products')->group(function () {
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        });

        Route::middleware('permission:create_products')->group(function () {
            Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        });

        Route::middleware('permission:update_products')->group(function () {
            Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        });

        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy')
            ->middleware('permission:delete_products');

        // Orders
        Route::middleware('permission:view_ecommerce_orders')->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        });

        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.update-status')
            ->middleware('permission:update_ecommerce_orders');

        Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])
            ->name('orders.invoice')
            ->middleware('permission:view_ecommerce_orders');

        // Coupons
        Route::middleware('permission:view_coupons')->group(function () {
            Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index');
        });

        Route::middleware('permission:create_coupons')->group(function () {
            Route::get('coupons/create', [CouponController::class, 'create'])->name('coupons.create');
            Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
        });

        Route::middleware('permission:update_coupons')->group(function () {
            Route::get('coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
            Route::put('coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        });

        Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])
            ->name('coupons.destroy')
            ->middleware('permission:delete_coupons');
    });
