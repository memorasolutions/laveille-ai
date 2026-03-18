<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Ecommerce\Models\Address;
use Modules\Ecommerce\Models\DigitalAsset;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create(['name' => 'Client', 'email' => 'client@test.com', 'password' => bcrypt('password')]);
    $this->otherUser = User::create(['name' => 'Other', 'email' => 'other@test.com', 'password' => bcrypt('password')]);
});

// --- Dashboard ---

test('guest is redirected to login', function () {
    $this->get(route('customer.dashboard'))->assertRedirect();
});

test('authenticated user can see dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('customer.dashboard'))
        ->assertOk()
        ->assertViewHas('totalOrders')
        ->assertViewHas('pendingCount')
        ->assertViewHas('recentOrders');
});

test('dashboard shows correct order counts', function () {
    Order::create(['user_id' => $this->user->id, 'order_number' => 'CP-001', 'status' => 'paid', 'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    Order::create(['user_id' => $this->user->id, 'order_number' => 'CP-002', 'status' => 'pending', 'subtotal' => 30, 'total' => 30, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    Order::create(['user_id' => $this->otherUser->id, 'order_number' => 'CP-003', 'status' => 'paid', 'subtotal' => 20, 'total' => 20, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $response = $this->actingAs($this->user)->get(route('customer.dashboard'));

    $response->assertOk();
    expect($response->viewData('totalOrders'))->toBe(2)
        ->and($response->viewData('pendingCount'))->toBe(1);
});

// --- Orders ---

test('user can see their orders list', function () {
    Order::create(['user_id' => $this->user->id, 'order_number' => 'CP-010', 'status' => 'paid', 'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $this->actingAs($this->user)
        ->get(route('customer.orders'))
        ->assertOk()
        ->assertSee('CP-010');
});

test('user cannot see other users orders in list', function () {
    Order::create(['user_id' => $this->otherUser->id, 'order_number' => 'CP-OTHER', 'status' => 'paid', 'subtotal' => 20, 'total' => 20, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $this->actingAs($this->user)
        ->get(route('customer.orders'))
        ->assertOk()
        ->assertDontSee('CP-OTHER');
});

// --- Order detail ---

test('user can see their own order detail', function () {
    $order = Order::create(['user_id' => $this->user->id, 'order_number' => 'CP-020', 'status' => 'paid', 'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $this->actingAs($this->user)
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertSee('CP-020');
});

test('user gets 403 on other user order', function () {
    $order = Order::create(['user_id' => $this->otherUser->id, 'order_number' => 'CP-021', 'status' => 'paid', 'subtotal' => 100, 'total' => 100, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);

    $this->actingAs($this->user)
        ->get(route('customer.orders.show', $order))
        ->assertForbidden();
});

// --- Downloads ---

test('downloads page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('customer.downloads'))
        ->assertOk()
        ->assertViewHas('downloads');
});

test('downloads shows available digital assets', function () {
    $product = Product::create(['name' => 'Ebook', 'slug' => 'ebook-portal', 'price' => 19.99]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'EB-P01', 'price' => 19.99, 'stock' => 999, 'is_active' => true]);

    $order = Order::create(['user_id' => $this->user->id, 'order_number' => 'CP-DL1', 'status' => 'paid', 'subtotal' => 20, 'total' => 20, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0]);
    OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'product_name' => 'Ebook', 'variant_label' => 'EB-P01', 'price' => 19.99, 'quantity' => 1, 'total' => 19.99]);

    DigitalAsset::create(['product_id' => $product->id, 'file_path' => 'digital/test.pdf', 'original_filename' => 'guide.pdf']);

    $response = $this->actingAs($this->user)->get(route('customer.downloads'));

    $response->assertOk();
    expect($response->viewData('downloads'))->toHaveCount(1);
});

// --- Addresses ---

test('user can view their addresses', function () {
    Address::create(['user_id' => $this->user->id, 'type' => 'shipping', 'first_name' => 'Jean', 'last_name' => 'Test', 'address_line_1' => '123 Rue', 'city' => 'Montréal', 'state' => 'QC', 'postal_code' => 'H2X1Y4', 'country' => 'CA']);

    $this->actingAs($this->user)
        ->get(route('customer.addresses'))
        ->assertOk()
        ->assertSee('Jean');
});

test('user can create an address', function () {
    $this->actingAs($this->user)
        ->post(route('customer.addresses.store'), [
            'type' => 'shipping',
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'address_line_1' => '456 Ave',
            'city' => 'Québec',
            'state' => 'QC',
            'postal_code' => 'G1R2S5',
            'country' => 'CA',
        ])
        ->assertRedirect();

    expect(Address::where('user_id', $this->user->id)->count())->toBe(1);
});

test('default address unsets previous default of same type', function () {
    $addr1 = Address::create(['user_id' => $this->user->id, 'type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'address_line_1' => '1 Rue', 'city' => 'MTL', 'state' => 'QC', 'postal_code' => 'H1A1A1', 'country' => 'CA', 'is_default' => true]);

    $this->actingAs($this->user)
        ->post(route('customer.addresses.store'), [
            'type' => 'shipping',
            'first_name' => 'C',
            'last_name' => 'D',
            'address_line_1' => '2 Rue',
            'city' => 'QC',
            'state' => 'QC',
            'postal_code' => 'G1A1A1',
            'country' => 'CA',
            'is_default' => '1',
        ]);

    expect($addr1->fresh()->is_default)->toBeFalse();
});

test('user cannot delete another users address', function () {
    $address = Address::create(['user_id' => $this->otherUser->id, 'type' => 'billing', 'first_name' => 'X', 'last_name' => 'Y', 'address_line_1' => '9 Rue', 'city' => 'MTL', 'state' => 'QC', 'postal_code' => 'H1A1A1', 'country' => 'CA']);

    $this->actingAs($this->user)
        ->delete(route('customer.addresses.destroy', $address))
        ->assertForbidden();

    expect(Address::find($address->id))->not->toBeNull();
});

test('user can delete their own address', function () {
    $address = Address::create(['user_id' => $this->user->id, 'type' => 'shipping', 'first_name' => 'Del', 'last_name' => 'Test', 'address_line_1' => '5 Rue', 'city' => 'MTL', 'state' => 'QC', 'postal_code' => 'H1A1A1', 'country' => 'CA']);

    $this->actingAs($this->user)
        ->delete(route('customer.addresses.destroy', $address))
        ->assertRedirect();

    expect(Address::find($address->id))->toBeNull();
});
