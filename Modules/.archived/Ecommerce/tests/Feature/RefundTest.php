<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Models\Refund;
use Modules\Ecommerce\Services\RefundService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'view_ecommerce']);
    Permission::firstOrCreate(['name' => 'view_ecommerce_orders']);
    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');

    $this->user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('password')]);

    config(['modules.ecommerce.stock.track_inventory' => true]);
});

// --- Model ---

test('refund can be created', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-001', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    $refund = Refund::create([
        'order_id' => $order->id,
        'user_id' => $this->user->id,
        'amount' => 50.00,
        'reason' => 'Produit défectueux',
    ]);

    expect($refund->exists)->toBeTrue()
        ->and($refund->status)->toBe('pending')
        ->and($refund->amount)->toBe(50.00);
});

test('pending scope works', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-002', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    Refund::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'amount' => 50, 'status' => 'pending']);
    Refund::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'amount' => 30, 'status' => 'approved']);

    expect(Refund::pending()->count())->toBe(1);
});

// --- Service ---

test('refund service can request refund', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-003', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    $service = app(RefundService::class);
    $refund = $service->requestRefund($order, 75.00, 'Mauvaise taille');

    expect($refund->status)->toBe('pending')
        ->and($refund->amount)->toBe(75.00)
        ->and($refund->reason)->toBe('Mauvaise taille');
});

test('refund service approve restores stock', function () {
    $product = Product::create(['name' => 'T', 'slug' => 'ref-stock', 'price' => 50]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'REF-001', 'price' => 50, 'stock' => 5, 'is_active' => true]);

    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-004', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $order->items()->create(['variant_id' => $variant->id, 'quantity' => 2, 'price' => 50, 'total' => 100, 'product_name' => 'T', 'variant_label' => 'REF-001']);

    $refund = Refund::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'amount' => 100]);

    $service = app(RefundService::class);
    $result = $service->approveRefund($refund, $this->admin, 'Approuvé');

    expect($result->status)->toBe('approved')
        ->and($result->processed_by)->toBe($this->admin->id)
        ->and($variant->fresh()->stock)->toBe(7);
});

test('refund service reject sets status', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-005', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    $refund = Refund::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'amount' => 50]);

    $service = app(RefundService::class);
    $result = $service->rejectRefund($refund, $this->admin, 'Hors délai');

    expect($result->status)->toBe('rejected')
        ->and($result->notes)->toBe('Hors délai')
        ->and($result->processed_at)->not->toBeNull();
});

// --- Admin ---

test('admin can list refunds', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-006', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    Refund::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'amount' => 50, 'reason' => 'Test']);

    $this->actingAs($this->admin)
        ->get(route('admin.ecommerce.refunds.index'))
        ->assertOk()
        ->assertSee('50');
});

test('admin can approve refund', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-007', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $refund = Refund::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'amount' => 50]);

    $this->actingAs($this->admin)
        ->patch(route('admin.ecommerce.refunds.approve', $refund))
        ->assertRedirect(route('admin.ecommerce.refunds.index'));

    expect($refund->fresh()->status)->toBe('approved');
});

test('admin can reject refund', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-REF-008', 'status' => 'paid',
        'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    $refund = Refund::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'amount' => 50]);

    $this->actingAs($this->admin)
        ->patch(route('admin.ecommerce.refunds.reject', $refund))
        ->assertRedirect(route('admin.ecommerce.refunds.index'));

    expect($refund->fresh()->status)->toBe('rejected');
});
