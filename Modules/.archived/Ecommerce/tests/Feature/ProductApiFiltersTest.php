<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Ecommerce\Models\Product;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Product::create(['name' => 'Alpha Widget', 'slug' => 'alpha', 'price' => 25, 'is_featured' => true, 'is_active' => true]);
    Product::create(['name' => 'Beta Gadget', 'slug' => 'beta', 'price' => 75, 'is_featured' => false, 'is_active' => true]);
    Product::create(['name' => 'Gamma Tool', 'slug' => 'gamma', 'price' => 150, 'is_featured' => true, 'is_active' => true]);
});

test('filter by min_price', function () {
    $data = $this->getJson('/api/ecommerce/products?min_price=50')->assertOk()->json('data.data');
    expect($data)->toHaveCount(2);
});

test('filter by max_price', function () {
    $data = $this->getJson('/api/ecommerce/products?max_price=50')->assertOk()->json('data.data');
    expect($data)->toHaveCount(1);
});

test('filter by price range', function () {
    $data = $this->getJson('/api/ecommerce/products?min_price=20&max_price=100')->assertOk()->json('data.data');
    expect($data)->toHaveCount(2);
});

test('filter by is_featured', function () {
    $data = $this->getJson('/api/ecommerce/products?is_featured=1')->assertOk()->json('data.data');
    expect($data)->toHaveCount(2);
});

test('sort by price_asc', function () {
    $data = $this->getJson('/api/ecommerce/products?sort_by=price_asc')->assertOk()->json('data.data');
    expect((float) $data[0]['price'])->toBeLessThanOrEqual((float) $data[count($data) - 1]['price']);
});

test('sort by price_desc', function () {
    $data = $this->getJson('/api/ecommerce/products?sort_by=price_desc')->assertOk()->json('data.data');
    expect((float) $data[0]['price'])->toBeGreaterThanOrEqual((float) $data[count($data) - 1]['price']);
});

test('search filter works', function () {
    $data = $this->getJson('/api/ecommerce/products?search=Widget')->assertOk()->json('data.data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Alpha Widget');
});

test('per_page limits results', function () {
    $this->getJson('/api/ecommerce/products?per_page=2')
        ->assertOk()
        ->assertJsonPath('data.per_page', 2);
});

test('inactive products are excluded', function () {
    Product::create(['name' => 'Inactive', 'slug' => 'inactive', 'price' => 10, 'is_active' => false]);

    $data = $this->getJson('/api/ecommerce/products')->assertOk()->json('data.data');
    $names = array_column($data, 'name');
    expect($names)->not->toContain('Inactive');
});
