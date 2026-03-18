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
    $this->productA = Product::create(['name' => 'Product A', 'slug' => 'product-a', 'price' => 50]);
    $this->productB = Product::create(['name' => 'Product B', 'slug' => 'product-b', 'price' => 75]);
    $this->productC = Product::create(['name' => 'Product C', 'slug' => 'product-c', 'price' => 100]);
});

test('product can have cross-sell relations', function () {
    $this->productA->crossSells()->attach($this->productB->id, ['type' => 'cross_sell', 'sort_order' => 0]);

    expect($this->productA->crossSells)->toHaveCount(1)
        ->and($this->productA->crossSells->first()->id)->toBe($this->productB->id);
});

test('product can have up-sell relations', function () {
    $this->productA->upSells()->attach($this->productC->id, ['type' => 'up_sell', 'sort_order' => 0]);

    expect($this->productA->upSells)->toHaveCount(1)
        ->and($this->productA->upSells->first()->id)->toBe($this->productC->id);
});

test('cross-sell and up-sell are separate', function () {
    $this->productA->crossSells()->attach($this->productB->id, ['type' => 'cross_sell', 'sort_order' => 0]);
    $this->productA->upSells()->attach($this->productC->id, ['type' => 'up_sell', 'sort_order' => 0]);

    expect($this->productA->crossSells)->toHaveCount(1)
        ->and($this->productA->upSells)->toHaveCount(1)
        ->and($this->productA->crossSells->first()->id)->toBe($this->productB->id)
        ->and($this->productA->upSells->first()->id)->toBe($this->productC->id);
});

test('sort order is respected', function () {
    $this->productA->crossSells()->attach($this->productC->id, ['type' => 'cross_sell', 'sort_order' => 2]);
    $this->productA->crossSells()->attach($this->productB->id, ['type' => 'cross_sell', 'sort_order' => 1]);

    $crossSells = $this->productA->crossSells()->get();

    expect($crossSells->first()->id)->toBe($this->productB->id)
        ->and($crossSells->last()->id)->toBe($this->productC->id);
});

test('API returns related products', function () {
    $this->productA->crossSells()->attach($this->productB->id, ['type' => 'cross_sell', 'sort_order' => 0]);
    $this->productA->upSells()->attach($this->productC->id, ['type' => 'up_sell', 'sort_order' => 0]);

    $this->getJson("/api/ecommerce/products/{$this->productA->id}/related")
        ->assertOk()
        ->assertJsonCount(1, 'data.cross_sells')
        ->assertJsonCount(1, 'data.up_sells');
});

test('API filters inactive related products', function () {
    $inactive = Product::create(['name' => 'Inactive', 'slug' => 'inactive', 'price' => 10, 'is_active' => false]);
    $this->productA->crossSells()->attach($inactive->id, ['type' => 'cross_sell', 'sort_order' => 0]);

    $this->getJson("/api/ecommerce/products/{$this->productA->id}/related")
        ->assertOk()
        ->assertJsonCount(0, 'data.cross_sells');
});
