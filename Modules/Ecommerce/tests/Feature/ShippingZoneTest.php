<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Models\ShippingMethod;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Ecommerce\Services\ShippingService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'manage_settings']);
    Permission::firstOrCreate(['name' => 'view_ecommerce']);
    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');
});

// --- Models ---

test('shipping zone can be created with regions', function () {
    $zone = ShippingZone::create(['name' => 'Québec', 'regions' => ['QC'], 'is_active' => true]);

    expect($zone->exists)->toBeTrue()
        ->and($zone->regions)->toBe(['QC'])
        ->and($zone->is_active)->toBeTrue();
});

test('shipping method belongs to zone', function () {
    $zone = ShippingZone::create(['name' => 'Ontario', 'regions' => ['ON']]);
    $zone->methods()->create(['name' => 'Standard', 'type' => 'flat_rate', 'cost' => 9.99]);

    $method = ShippingMethod::with('zone')->where('shipping_zone_id', $zone->id)->first();

    expect($method)->not->toBeNull()
        ->and($method->shipping_zone_id)->toBe($zone->id)
        ->and($zone->fresh()->methods)->toHaveCount(1);
});

test('forProvince scope finds matching zone', function () {
    ShippingZone::create(['name' => 'Québec', 'regions' => ['QC', 'ON'], 'is_active' => true]);
    ShippingZone::create(['name' => 'West', 'regions' => ['BC', 'AB'], 'is_active' => true]);

    expect(ShippingZone::forProvince('QC')->count())->toBe(1)
        ->and(ShippingZone::forProvince('BC')->count())->toBe(1)
        ->and(ShippingZone::forProvince('SK')->count())->toBe(0);
});

test('active scope filters inactive zones', function () {
    ShippingZone::create(['name' => 'Active', 'regions' => ['QC'], 'is_active' => true]);
    ShippingZone::create(['name' => 'Inactive', 'regions' => ['ON'], 'is_active' => false]);

    expect(ShippingZone::active()->count())->toBe(1);
});

// --- Service ---

test('shipping service uses zone method when province matches', function () {
    $zone = ShippingZone::create(['name' => 'Québec', 'regions' => ['QC'], 'is_active' => true]);
    $zone->methods()->create(['name' => 'Express', 'type' => 'flat_rate', 'cost' => 15.00, 'is_active' => true]);

    $product = Product::create(['name' => 'T', 'slug' => 'ship-test', 'price' => 50]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SH-001', 'price' => 50, 'stock' => 10, 'is_active' => true]);

    $user = User::create(['name' => 'Buyer', 'email' => 'buyer@test.com', 'password' => bcrypt('password')]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $service = new ShippingService;
    $cost = $service->calculateShipping($cart, null, 'QC');

    expect($cost)->toBe(15.00);
});

test('shipping service falls back to config when no zone matches', function () {
    config(['modules.ecommerce.shipping.flat_rate' => 9.99, 'modules.ecommerce.shipping.free_threshold' => 100]);

    $product = Product::create(['name' => 'T', 'slug' => 'ship-fb', 'price' => 30]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SH-002', 'price' => 30, 'stock' => 10, 'is_active' => true]);

    $user = User::create(['name' => 'Buyer', 'email' => 'buyer2@test.com', 'password' => bcrypt('password')]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $service = new ShippingService;
    $cost = $service->calculateShipping($cart, null, 'SK');

    expect($cost)->toBe(9.99);
});

test('shipping service percentage method calculates correctly', function () {
    $zone = ShippingZone::create(['name' => 'Ontario', 'regions' => ['ON'], 'is_active' => true]);
    $zone->methods()->create(['name' => '10%', 'type' => 'percentage', 'cost' => 10, 'is_active' => true]);

    $product = Product::create(['name' => 'T', 'slug' => 'ship-pct', 'price' => 100]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SH-003', 'price' => 100, 'stock' => 10, 'is_active' => true]);

    $user = User::create(['name' => 'Buyer', 'email' => 'buyer3@test.com', 'password' => bcrypt('password')]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $service = new ShippingService;
    $cost = $service->calculateShipping($cart, null, 'ON');

    expect($cost)->toBe(10.00);
});

test('getAvailableMethods returns zone methods for province', function () {
    $zone = ShippingZone::create(['name' => 'Québec', 'regions' => ['QC'], 'is_active' => true]);
    $zone->methods()->create(['name' => 'Standard', 'type' => 'flat_rate', 'cost' => 9.99, 'is_active' => true]);
    $zone->methods()->create(['name' => 'Express', 'type' => 'flat_rate', 'cost' => 19.99, 'is_active' => true]);

    $product = Product::create(['name' => 'T', 'slug' => 'ship-avail', 'price' => 50]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SH-004', 'price' => 50, 'stock' => 10, 'is_active' => true]);

    $user = User::create(['name' => 'Buyer', 'email' => 'buyer4@test.com', 'password' => bcrypt('password')]);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $service = new ShippingService;
    $methods = $service->getAvailableMethods($cart, 'QC');

    expect($methods)->toHaveCount(2)
        ->and($methods[0]['label'])->toBe('Standard');
});

// --- Admin CRUD ---

test('admin can list shipping zones', function () {
    ShippingZone::create(['name' => 'Québec', 'regions' => ['QC']]);

    $this->actingAs($this->admin)
        ->get(route('admin.ecommerce.shipping-zones.index'))
        ->assertOk()
        ->assertSee('Québec');
});

test('admin can create shipping zone', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.ecommerce.shipping-zones.store'), [
            'name' => 'Canada Est',
            'regions' => ['QC', 'ON', 'NB'],
            'is_active' => true,
            'methods' => [
                ['name' => 'Standard', 'type' => 'flat_rate', 'cost' => 9.99],
            ],
        ])
        ->assertRedirect(route('admin.ecommerce.shipping-zones.index'));

    expect(ShippingZone::where('name', 'Canada Est')->exists())->toBeTrue();
    expect(ShippingMethod::where('name', 'Standard')->exists())->toBeTrue();
});

test('admin can update shipping zone', function () {
    $zone = ShippingZone::create(['name' => 'Old', 'regions' => ['QC']]);

    $this->actingAs($this->admin)
        ->put(route('admin.ecommerce.shipping-zones.update', $zone), [
            'name' => 'Updated',
            'regions' => ['QC', 'ON'],
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.ecommerce.shipping-zones.index'));

    expect($zone->fresh()->name)->toBe('Updated')
        ->and($zone->fresh()->regions)->toBe(['QC', 'ON']);
});

test('admin can delete shipping zone', function () {
    $zone = ShippingZone::create(['name' => 'Delete Me', 'regions' => ['QC']]);
    $zone->methods()->create(['name' => 'Standard', 'type' => 'flat_rate', 'cost' => 5]);

    $this->actingAs($this->admin)
        ->delete(route('admin.ecommerce.shipping-zones.destroy', $zone))
        ->assertRedirect(route('admin.ecommerce.shipping-zones.index'));

    expect(ShippingZone::find($zone->id))->toBeNull();
    expect(ShippingMethod::where('shipping_zone_id', $zone->id)->count())->toBe(0);
});
