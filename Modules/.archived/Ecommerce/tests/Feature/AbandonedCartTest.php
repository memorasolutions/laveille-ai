<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Ecommerce\Jobs\ProcessAbandonedCarts;
use Modules\Ecommerce\Models\AbandonedCartReminder;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Notifications\AbandonedCartNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'view_ecommerce']);
    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');

    $this->user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('password')]);

    config(['modules.ecommerce.abandoned_cart.schedule' => [1 => 1, 24 => 2, 72 => 3]]);
    config(['modules.ecommerce.stock.track_inventory' => true]);
});

// --- Model ---

test('abandoned cart reminder can be created', function () {
    $cart = Cart::create(['user_id' => $this->user->id]);

    $reminder = AbandonedCartReminder::create([
        'cart_id' => $cart->id,
        'user_id' => $this->user->id,
        'reminder_number' => 1,
        'sent_at' => now(),
    ]);

    expect($reminder->exists)->toBeTrue()
        ->and($reminder->reminder_number)->toBe(1);
});

test('not_recovered scope works', function () {
    $cart1 = Cart::create(['user_id' => $this->user->id]);
    $cart2 = Cart::create(['user_id' => $this->admin->id]);

    AbandonedCartReminder::create(['cart_id' => $cart1->id, 'user_id' => $this->user->id, 'reminder_number' => 1, 'sent_at' => now()]);
    AbandonedCartReminder::create(['cart_id' => $cart2->id, 'user_id' => $this->admin->id, 'reminder_number' => 1, 'sent_at' => now(), 'recovered_at' => now()]);

    expect(AbandonedCartReminder::notRecovered()->count())->toBe(1);
});

// --- Job ---

test('process job sends reminder for abandoned cart', function () {
    Notification::fake();

    $product = Product::create(['name' => 'T', 'slug' => 'ab-test', 'price' => 20]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'AB-001', 'price' => 20, 'stock' => 10, 'is_active' => true]);

    $cart = Cart::create(['user_id' => $this->user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
    DB::table('ecommerce_carts')->where('id', $cart->id)->update(['updated_at' => now()->subHours(2)]);

    dispatch_sync(new ProcessAbandonedCarts);

    Notification::assertSentTo($this->user, AbandonedCartNotification::class, fn ($n) => $n->reminderNumber === 1);
    expect(AbandonedCartReminder::where('cart_id', $cart->id)->count())->toBe(1);
});

test('process job skips cart with recent order', function () {
    Notification::fake();

    $product = Product::create(['name' => 'T', 'slug' => 'ab-order', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'AB-002', 'price' => 10, 'stock' => 10, 'is_active' => true]);

    $cart = Cart::create(['user_id' => $this->user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
    DB::table('ecommerce_carts')->where('id', $cart->id)->update(['updated_at' => now()->subHours(2)]);

    Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-AB-001', 'status' => 'paid',
        'subtotal' => 10, 'total' => 10, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    dispatch_sync(new ProcessAbandonedCarts);

    Notification::assertNothingSent();
});

test('process job skips empty cart', function () {
    Notification::fake();

    $cart = Cart::create(['user_id' => $this->user->id]);
    DB::table('ecommerce_carts')->where('id', $cart->id)->update(['updated_at' => now()->subHours(2)]);

    dispatch_sync(new ProcessAbandonedCarts);

    Notification::assertNothingSent();
});

test('process job does not send duplicate reminder', function () {
    Notification::fake();

    $product = Product::create(['name' => 'T', 'slug' => 'ab-dup', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'AB-003', 'price' => 10, 'stock' => 10, 'is_active' => true]);

    $cart = Cart::create(['user_id' => $this->user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
    DB::table('ecommerce_carts')->where('id', $cart->id)->update(['updated_at' => now()->subHours(2)]);

    AbandonedCartReminder::create(['cart_id' => $cart->id, 'user_id' => $this->user->id, 'reminder_number' => 1, 'sent_at' => now()->subHour()]);

    dispatch_sync(new ProcessAbandonedCarts);

    Notification::assertNothingSent();
});

test('process job sends reminder 2 after 24 hours', function () {
    Notification::fake();

    $product = Product::create(['name' => 'T', 'slug' => 'ab-r2', 'price' => 10]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'AB-004', 'price' => 10, 'stock' => 10, 'is_active' => true]);

    $cart = Cart::create(['user_id' => $this->user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
    DB::table('ecommerce_carts')->where('id', $cart->id)->update(['updated_at' => now()->subHours(25)]);

    AbandonedCartReminder::create(['cart_id' => $cart->id, 'user_id' => $this->user->id, 'reminder_number' => 1, 'sent_at' => now()->subHours(24)]);

    dispatch_sync(new ProcessAbandonedCarts);

    Notification::assertSentTo($this->user, AbandonedCartNotification::class, fn ($n) => $n->reminderNumber === 2);
    expect(AbandonedCartReminder::where('cart_id', $cart->id)->where('reminder_number', 2)->exists())->toBeTrue();
});
