<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Models\Promotion;
use Modules\Ecommerce\Services\PromotionService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// --- Promotion Model ---

test('promotion factory creates valid promotion', function () {
    $promo = Promotion::factory()->create();

    expect($promo->exists)->toBeTrue()
        ->and($promo->is_active)->toBeTrue()
        ->and($promo->isValid())->toBeTrue();
});

test('expired promotion is not valid', function () {
    $promo = Promotion::factory()->expired()->create();

    expect($promo->isValid())->toBeFalse();
});

test('inactive promotion is not valid', function () {
    $promo = Promotion::factory()->inactive()->create();

    expect($promo->isValid())->toBeFalse();
});

test('exhausted promotion is not valid', function () {
    $promo = Promotion::factory()->exhausted()->create();

    expect($promo->isValid())->toBeFalse();
});

test('scopeValid filters correctly', function () {
    Promotion::factory()->create(); // valid
    Promotion::factory()->expired()->create();
    Promotion::factory()->inactive()->create();
    Promotion::factory()->exhausted()->create();

    expect(Promotion::valid()->count())->toBe(1);
});

test('appliesToProduct returns true for all target', function () {
    $promo = Promotion::factory()->create(['applies_to' => 'all']);

    expect($promo->appliesToProduct(999))->toBeTrue();
});

test('appliesToProduct returns true for targeted product', function () {
    $promo = Promotion::factory()->forProducts([1, 5, 10])->create();

    expect($promo->appliesToProduct(5))->toBeTrue()
        ->and($promo->appliesToProduct(99))->toBeFalse();
});

test('appliesToCategory works for targeted categories', function () {
    $promo = Promotion::factory()->forCategories([2, 7])->create();

    expect($promo->appliesToCategory(7))->toBeTrue()
        ->and($promo->appliesToCategory(99))->toBeFalse();
});

test('meetsConditions checks min_order', function () {
    $promo = Promotion::factory()->withMinOrder(50.0)->create();

    expect($promo->meetsConditions(60.0, 1))->toBeTrue()
        ->and($promo->meetsConditions(30.0, 1))->toBeFalse();
});

test('meetsConditions checks min_qty', function () {
    $promo = Promotion::factory()->withMinQty(3)->create();

    expect($promo->meetsConditions(100.0, 5))->toBeTrue()
        ->and($promo->meetsConditions(100.0, 2))->toBeFalse();
});

// --- PromotionService ---

function createCartWithItems(float $price = 25.00, int $qty = 2): Cart
{
    $cat = Category::create(['name' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'position' => 0]);
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product-'.uniqid(),
        'price' => $price,
    ]);
    $product->categories()->attach($cat->id);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TST-'.uniqid(),
        'price' => $price,
        'stock' => 100,
        'is_active' => true,
    ]);
    $cart = Cart::create(['session_id' => uniqid()]);
    CartItem::create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => $qty]);

    return $cart->load('items.variant.product.categories');
}

test('percentage discount calculates correctly', function () {
    $cart = createCartWithItems(50.0, 2); // subtotal = 100
    Promotion::factory()->percentage(20)->create();

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    expect($discount)->toBe(20.0); // 20% of 100
});

test('fixed discount calculates correctly', function () {
    $cart = createCartWithItems(50.0, 2); // subtotal = 100
    Promotion::factory()->fixed(15)->create();

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    expect($discount)->toBe(15.0);
});

test('fixed discount capped at subtotal', function () {
    $cart = createCartWithItems(5.0, 1); // subtotal = 5
    Promotion::factory()->fixed(50)->create();

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    expect($discount)->toBe(5.0); // capped at subtotal
});

test('tiered pricing applies correct tier', function () {
    $cart = createCartWithItems(60.0, 2); // subtotal = 120
    Promotion::factory()->tiered()->create();

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    // 120 >= 100 → 10% tier → 12.0
    expect($discount)->toBe(12.0);
});

test('free shipping promotion detected', function () {
    createCartWithItems(50.0, 1);
    Promotion::factory()->freeShipping()->create();

    $service = app(PromotionService::class);

    expect($service->grantsFreeShipping(Cart::first()->load('items.variant.product')))->toBeTrue();
});

test('bogo calculates free items', function () {
    $cart = createCartWithItems(30.0, 6); // 6 items, buy 2 get 1 → 2 sets → 2 free
    Promotion::factory()->bogo(2, 1, 100)->create();

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    expect($discount)->toBe(60.0); // 2 free × 30 each
});

test('promotion with min_order not met returns 0', function () {
    $cart = createCartWithItems(10.0, 1); // subtotal = 10
    Promotion::factory()->percentage(20)->withMinOrder(50.0)->create();

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    expect($discount)->toBe(0.0);
});

test('best promotion returns highest discount', function () {
    $cart = createCartWithItems(50.0, 2); // subtotal = 100
    Promotion::factory()->percentage(10)->create(['name' => 'Small', 'priority' => 1]);
    Promotion::factory()->percentage(25)->create(['name' => 'Big', 'priority' => 2]);

    $service = app(PromotionService::class);
    $best = $service->bestPromotion($cart);

    expect($best)->not->toBeNull()
        ->and($best->name)->toBe('Big');
});

test('non-stackable promotion blocks others', function () {
    $cart = createCartWithItems(50.0, 2); // subtotal = 100
    // First: non-stackable 20%
    Promotion::factory()->percentage(20)->create(['is_stackable' => false, 'priority' => 10]);
    // Second: non-stackable 10%
    Promotion::factory()->percentage(10)->create(['is_stackable' => false, 'priority' => 5]);

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    // Only the highest priority (20%) applies, second blocked
    expect($discount)->toBe(20.0);
});

test('stackable promotions accumulate', function () {
    $cart = createCartWithItems(50.0, 2); // subtotal = 100
    Promotion::factory()->percentage(10)->stackable()->create(['priority' => 10]);
    Promotion::factory()->fixed(5)->stackable()->create(['priority' => 5]);

    $service = app(PromotionService::class);
    $discount = $service->applyToCart($cart);

    // 10% of 100 = 10 + 5 fixed = 15
    expect($discount)->toBe(15.0);
});
