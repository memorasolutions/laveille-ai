<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Services\SalesAnalyticsService;
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
});

// --- Service ---

test('getSummary returns correct totals', function () {
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-001', 'status' => 'paid', 'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-002', 'status' => 'pending', 'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-003', 'status' => 'delivered', 'subtotal' => 75, 'total' => 75, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $service = new SalesAnalyticsService;
    $summary = $service->getSummary();

    expect($summary['total_orders'])->toBe(3)
        ->and($summary['total_revenue'])->toBe(175.0)
        ->and($summary['pending_orders'])->toBe(1)
        ->and($summary['paid_orders'])->toBe(1)
        ->and($summary['delivered_orders'])->toBe(1);
});

test('getSummary respects date range', function () {
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-010', 'status' => 'paid', 'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0, 'created_at' => now()->subDays(5)]);

    $service = new SalesAnalyticsService;

    $recent = $service->getSummary(now()->subDays(10), now());
    expect($recent['total_orders'])->toBe(1);

    $old = $service->getSummary(now()->subDays(100), now()->subDays(50));
    expect($old['total_orders'])->toBe(0);
});

test('getRevenueByDay groups correctly', function () {
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-020', 'status' => 'paid', 'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-021', 'status' => 'paid', 'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $service = new SalesAnalyticsService;
    $byDay = $service->getRevenueByDay();

    expect($byDay)->toHaveCount(1)
        ->and((float) $byDay->first()->revenue)->toBe(80.0)
        ->and((int) $byDay->first()->orders)->toBe(2);
});

test('getTopProducts returns ranked products', function () {
    $product = Product::create(['name' => 'Widget A', 'slug' => 'widget-a', 'price' => 25]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'WA-01', 'price' => 25, 'stock' => 100, 'is_active' => true]);

    $order = Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-030', 'status' => 'paid', 'subtotal' => 75, 'total' => 75, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'product_name' => 'Widget A', 'variant_label' => 'WA-01', 'price' => 25, 'quantity' => 3, 'total' => 75]);

    $service = new SalesAnalyticsService;
    $top = $service->getTopProducts(5);

    expect($top)->toHaveCount(1)
        ->and($top->first()->product_name)->toBe('Widget A')
        ->and((int) $top->first()->total_quantity)->toBe(3);
});

test('getOrdersByStatus returns grouped counts', function () {
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-040', 'status' => 'paid', 'subtotal' => 10, 'total' => 10, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-041', 'status' => 'paid', 'subtotal' => 20, 'total' => 20, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    Order::create(['user_id' => $this->user->id, 'order_number' => 'AN-042', 'status' => 'pending', 'subtotal' => 5, 'total' => 5, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $service = new SalesAnalyticsService;
    $byStatus = $service->getOrdersByStatus();

    expect($byStatus['paid'])->toBe(2)
        ->and($byStatus['pending'])->toBe(1);
});

// --- Admin route ---

test('admin can access analytics page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.ecommerce.analytics'))
        ->assertOk()
        ->assertViewHas('summary')
        ->assertViewHas('revenueByDay')
        ->assertViewHas('topProducts')
        ->assertViewHas('ordersByStatus');
});

test('admin can filter analytics by date range', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.ecommerce.analytics', ['from' => now()->subDays(7)->format('Y-m-d'), 'to' => now()->format('Y-m-d')]))
        ->assertOk();
});

test('non-admin gets 403 on analytics', function () {
    $this->actingAs($this->user)
        ->get(route('admin.ecommerce.analytics'))
        ->assertForbidden();
});
