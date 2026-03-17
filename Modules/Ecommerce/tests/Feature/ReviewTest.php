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
use Modules\Ecommerce\Models\Review;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['view_ecommerce', 'view_products', 'view_admin_panel'] as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }

    $role = Role::firstOrCreate(['name' => 'super_admin']);
    $role->syncPermissions(Permission::all());

    $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
    $this->admin->assignRole('super_admin');

    $this->user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('password')]);
});

// --- Model ---

test('review can be created', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'price' => 10]);
    $review = Review::create([
        'product_id' => $product->id,
        'user_id' => $this->user->id,
        'rating' => 5,
        'body' => 'Excellent produit !',
    ]);

    expect($review->exists)->toBeTrue()
        ->and($review->rating)->toBe(5)
        ->and($review->is_approved)->toBeFalse();
});

test('approved scope filters correctly', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-scope', 'price' => 10]);
    Review::create(['product_id' => $product->id, 'user_id' => $this->admin->id, 'rating' => 5, 'body' => 'Good', 'is_approved' => true]);
    Review::create(['product_id' => $product->id, 'user_id' => $this->user->id, 'rating' => 3, 'body' => 'OK', 'is_approved' => false]);

    expect(Review::approved()->count())->toBe(1)
        ->and(Review::pending()->count())->toBe(1);
});

test('one review per user per product', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-uniq', 'price' => 10]);
    Review::create(['product_id' => $product->id, 'user_id' => $this->user->id, 'rating' => 5, 'body' => 'First']);

    expect(fn () => Review::create(['product_id' => $product->id, 'user_id' => $this->user->id, 'rating' => 4, 'body' => 'Second']))
        ->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

// --- Admin ---

test('admin can access reviews index', function () {
    $this->actingAs($this->admin)->get(route('admin.ecommerce.reviews.index'))->assertOk();
});

test('admin can approve review', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-approve', 'price' => 10]);
    $review = Review::create(['product_id' => $product->id, 'user_id' => $this->user->id, 'rating' => 4, 'body' => 'Pending', 'is_approved' => false]);

    $this->actingAs($this->admin)->patch(route('admin.ecommerce.reviews.approve', $review))->assertRedirect();

    expect($review->fresh()->is_approved)->toBeTrue();
});

test('admin can reject review', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-reject', 'price' => 10]);
    $review = Review::create(['product_id' => $product->id, 'user_id' => $this->user->id, 'rating' => 2, 'body' => 'Bad']);

    $this->actingAs($this->admin)->delete(route('admin.ecommerce.reviews.reject', $review))->assertRedirect();

    expect(Review::find($review->id))->toBeNull();
});

// --- API ---

test('api returns approved reviews for product', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-api', 'price' => 10, 'is_active' => true]);
    Review::create(['product_id' => $product->id, 'user_id' => $this->admin->id, 'rating' => 5, 'body' => 'Great', 'is_approved' => true]);
    Review::create(['product_id' => $product->id, 'user_id' => $this->user->id, 'rating' => 3, 'body' => 'Meh', 'is_approved' => false]);

    $this->getJson("/api/ecommerce/products/{$product->id}/reviews")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.data');
});

test('api allows authenticated user to submit review', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-submit', 'price' => 10, 'is_active' => true]);

    $this->actingAs($this->user)
        ->postJson("/api/ecommerce/products/{$product->id}/reviews", ['rating' => 5, 'body' => 'Excellent !'])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    expect(Review::where('user_id', $this->user->id)->where('product_id', $product->id)->exists())->toBeTrue();
});

test('api prevents duplicate review', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-dup', 'price' => 10, 'is_active' => true]);

    $this->actingAs($this->user)->postJson("/api/ecommerce/products/{$product->id}/reviews", ['rating' => 5, 'body' => 'First'])->assertStatus(201);
    $this->actingAs($this->user)->postJson("/api/ecommerce/products/{$product->id}/reviews", ['rating' => 4, 'body' => 'Second'])->assertStatus(422);
});

test('api detects verified purchase', function () {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-verified', 'price' => 50, 'is_active' => true]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'REV-001', 'price' => 50, 'stock' => 10, 'is_active' => true]);

    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'INV-TEST-001', 'status' => 'paid',
        'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'variant_id' => $variant->id,
        'product_name' => 'Test', 'variant_label' => 'REV-001',
        'price' => 50, 'quantity' => 1, 'total' => 50,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/ecommerce/products/{$product->id}/reviews", ['rating' => 5, 'body' => 'Achat vérifié !']);

    $response->assertStatus(201);
    expect(Review::where('user_id', $this->user->id)->first()->is_verified_purchase)->toBeTrue();
});
