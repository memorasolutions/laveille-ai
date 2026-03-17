<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Ecommerce\Events\LowStockDetected;
use Modules\Ecommerce\Models\Address;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\InventoryService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['view_ecommerce', 'view_products', 'create_products', 'view_admin_panel'] as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }

    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');

    config(['modules.ecommerce.stock.track_inventory' => true]);
});

// --- InventoryService ---

test('inventory service can deduct stock', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-001', 'price' => 10, 'stock' => 10, 'low_stock_threshold' => 5, 'is_active' => true]);

    app(InventoryService::class)->deductStock($variant, 3);

    expect($variant->fresh()->stock)->toBe(7);
});

test('inventory service fires low stock event when threshold reached', function () {
    Event::fake([LowStockDetected::class]);

    $product = Product::create(['name' => 'Test', 'slug' => 'test-ls', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-002', 'price' => 10, 'stock' => 6, 'low_stock_threshold' => 5, 'is_active' => true]);

    app(InventoryService::class)->deductStock($variant, 2);

    Event::assertDispatched(LowStockDetected::class, fn ($e) => $e->variant->id === $variant->id);
});

test('inventory service does not fire event when stock above threshold', function () {
    Event::fake([LowStockDetected::class]);

    $product = Product::create(['name' => 'Test', 'slug' => 'test-nols', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-003', 'price' => 10, 'stock' => 20, 'low_stock_threshold' => 5, 'is_active' => true]);

    app(InventoryService::class)->deductStock($variant, 2);

    Event::assertNotDispatched(LowStockDetected::class);
});

test('canFulfill returns false when insufficient stock', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-cf', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-004', 'price' => 10, 'stock' => 2, 'is_active' => true]);

    expect(app(InventoryService::class)->canFulfill($variant, 5))->toBeFalse();
});

test('canFulfill returns true with backorder enabled', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-bo', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-005', 'price' => 10, 'stock' => 0, 'allow_backorder' => true, 'is_active' => true]);

    expect(app(InventoryService::class)->canFulfill($variant, 5))->toBeTrue();
});

test('restoreStock increments stock', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-rs', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-006', 'price' => 10, 'stock' => 3, 'is_active' => true]);

    app(InventoryService::class)->restoreStock($variant, 5);

    expect($variant->fresh()->stock)->toBe(8);
});

test('checkLowStock returns true when at threshold', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-cls', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-007', 'price' => 10, 'stock' => 5, 'low_stock_threshold' => 5, 'is_active' => true]);

    expect(app(InventoryService::class)->checkLowStock($variant))->toBeTrue();
});

// --- CartService backorder integration ---

test('cart service allows adding item with backorder when out of stock', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-cart-bo', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-008', 'price' => 10, 'stock' => 0, 'allow_backorder' => true, 'is_active' => true]);
    $cart = Cart::create(['user_id' => $this->admin->id]);

    $item = app(CartService::class)->addItem($cart, $variant, 3);

    expect($item->quantity)->toBe(3);
});

test('cart service rejects adding item without backorder when out of stock', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-cart-nobo', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-009', 'price' => 10, 'stock' => 0, 'allow_backorder' => false, 'is_active' => true]);
    $cart = Cart::create(['user_id' => $this->admin->id]);

    expect(fn () => app(CartService::class)->addItem($cart, $variant, 3))
        ->toThrow(\RuntimeException::class, 'Stock insuffisant.');
});

// --- Checkout stock validation ---

test('checkout validates stock at order time', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-co', 'price' => 50]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-010', 'price' => 50, 'stock' => 2, 'is_active' => true]);

    $cart = Cart::create(['user_id' => $this->admin->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 10]);

    $address = Address::create([
        'user_id' => $this->admin->id, 'label' => 'Home', 'first_name' => 'A', 'last_name' => 'B',
        'address_line_1' => '123 Rue', 'city' => 'Montréal', 'province' => 'QC', 'postal_code' => 'H2X1A1', 'country' => 'CA',
    ]);

    expect(fn () => app(CheckoutService::class)->createOrder($this->admin, $cart->load('items.variant.product'), $address, null, null, 'standard'))
        ->toThrow(\RuntimeException::class);
});

test('checkout deducts stock and triggers low stock event', function () {
    Event::fake([LowStockDetected::class]);

    $product = Product::create(['name' => 'Test', 'slug' => 'test-co-ls', 'price' => 50]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'INV-011', 'price' => 50, 'stock' => 7, 'low_stock_threshold' => 5, 'is_active' => true]);

    $cart = Cart::create(['user_id' => $this->admin->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 3]);

    $address = Address::create([
        'user_id' => $this->admin->id, 'label' => 'Home', 'first_name' => 'A', 'last_name' => 'B',
        'address_line_1' => '123 Rue', 'city' => 'Montréal', 'province' => 'QC', 'postal_code' => 'H2X1A1', 'country' => 'CA',
    ]);

    $order = app(CheckoutService::class)->createOrder($this->admin, $cart->load('items.variant.product'), $address, null, null, 'standard');

    expect($order->exists)->toBeTrue()
        ->and($variant->fresh()->stock)->toBe(4);

    Event::assertDispatched(LowStockDetected::class);
});
