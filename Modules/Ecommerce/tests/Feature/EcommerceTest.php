<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\TaxService;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['view_ecommerce', 'view_products', 'create_products', 'update_products', 'delete_products', 'view_ecommerce_orders', 'view_coupons', 'create_coupons', 'view_admin_panel'] as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }

    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');
});

// --- Module ---

test('module is loaded', fn () => expect(Module::has('Ecommerce'))->toBeTrue());

test('config is accessible', fn () => expect(config('modules.ecommerce.currency'))->toBe('CAD'));

test('routes are registered', fn () => expect(Route::has('admin.ecommerce.dashboard'))->toBeTrue());

// --- Models ---

test('category can be created', function () {
    $cat = Category::create(['name' => 'Vêtements', 'slug' => 'vetements', 'is_active' => true, 'position' => 0]);
    expect($cat->exists)->toBeTrue()
        ->and($cat->is_active)->toBeTrue();
});

test('product has categories relationship', function () {
    $cat = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'position' => 0]);
    $product = Product::create(['name' => 'T-shirt', 'slug' => 't-shirt', 'price' => 29.99]);
    $product->categories()->attach($cat->id);

    expect($product->categories)->toHaveCount(1)
        ->and($product->categories->first()->name)->toBe('Test');
});

test('coupon isValid returns true for active coupon', function () {
    $coupon = Coupon::create([
        'code' => 'VALID10', 'type' => 'percent', 'value' => 10,
        'is_active' => true, 'expires_at' => now()->addDays(30),
    ]);
    expect($coupon->isValid())->toBeTrue();
});

test('coupon isValid returns false for expired coupon', function () {
    $coupon = Coupon::create([
        'code' => 'EXPIRED', 'type' => 'percent', 'value' => 10,
        'is_active' => true, 'expires_at' => now()->subDay(),
    ]);
    expect($coupon->isValid())->toBeFalse();
});

// --- Services ---

test('tax service calculates correct tax', function () {
    $tax = app(TaxService::class);
    expect($tax->calculateTax(100.00))->toBe(14.98)
        ->and($tax->getTaxRate())->toBe(14.975);
});

test('cart service can add and remove items', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'price' => 50]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'TEST-001', 'price' => 50, 'stock' => 10, 'is_active' => true]);
    $cart = Cart::create(['user_id' => $this->admin->id]);

    $service = app(CartService::class);
    $item = $service->addItem($cart, $variant, 2);

    expect($item->quantity)->toBe(2)
        ->and($service->getTotal($cart->fresh()->load('items.variant')))->toBe(100.0)
        ->and($service->getItemCount($cart->fresh()->load('items')))->toBe(2);

    $service->removeItem($item);
    expect($cart->fresh()->items)->toHaveCount(0);
});

// --- Admin CRUD ---

test('admin can access dashboard', fn () => $this->actingAs($this->admin)->get(route('admin.ecommerce.dashboard'))->assertOk());

test('admin can access products index', fn () => $this->actingAs($this->admin)->get(route('admin.ecommerce.products.index'))->assertOk());

test('admin can access products create', fn () => $this->actingAs($this->admin)->get(route('admin.ecommerce.products.create'))->assertOk());

test('admin can store a product', function () {
    $this->actingAs($this->admin)->post(route('admin.ecommerce.products.store'), [
        'name' => 'Nouveau produit',
        'slug' => 'nouveau-produit',
        'price' => 49.99,
    ])->assertRedirect(route('admin.ecommerce.products.index'));

    expect(Product::where('slug', 'nouveau-produit')->exists())->toBeTrue();
});

test('admin can access categories index', fn () => $this->actingAs($this->admin)->get(route('admin.ecommerce.categories.index'))->assertOk());

test('admin can access orders index', fn () => $this->actingAs($this->admin)->get(route('admin.ecommerce.orders.index'))->assertOk());

test('admin can access coupons index', fn () => $this->actingAs($this->admin)->get(route('admin.ecommerce.coupons.index'))->assertOk());

test('unauthorized user gets 403', function () {
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('password')]);
    $this->actingAs($user)->get(route('admin.ecommerce.dashboard'))->assertForbidden();
});
