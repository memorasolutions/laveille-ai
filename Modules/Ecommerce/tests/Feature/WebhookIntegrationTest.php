<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Ecommerce\Events\LowStockDetected;
use Modules\Ecommerce\Events\OrderCreated;
use Modules\Ecommerce\Events\OrderPaid;
use Modules\Ecommerce\Events\OrderShipped;
use Modules\Ecommerce\Listeners\DispatchEcommerceWebhooks;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Webhooks\Enums\WebhookEvent;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create(['name' => 'Buyer', 'email' => 'buyer@test.com', 'password' => bcrypt('password')]);
    $this->product = Product::create(['name' => 'WH Test', 'slug' => 'wh-test', 'price' => 50]);
    $this->variant = ProductVariant::create(['product_id' => $this->product->id, 'sku' => 'WH-01', 'price' => 50, 'stock' => 10, 'is_active' => true]);
});

test('webhook event enum has ecommerce events', function () {
    expect(WebhookEvent::OrderCreated->value)->toBe('order.created')
        ->and(WebhookEvent::OrderPaid->value)->toBe('order.paid')
        ->and(WebhookEvent::OrderShipped->value)->toBe('order.shipped')
        ->and(WebhookEvent::OrderRefunded->value)->toBe('order.refunded')
        ->and(WebhookEvent::LowStockDetected->value)->toBe('inventory.low_stock');
});

test('listener handles OrderCreated without error', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'WH-001', 'status' => 'pending',
        'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    $listener = new DispatchEcommerceWebhooks;
    $listener->handle(new OrderCreated($order));

    expect(true)->toBeTrue();
});

test('listener handles OrderPaid without error', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'WH-002', 'status' => 'paid',
        'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
    ]);

    $listener = new DispatchEcommerceWebhooks;
    $listener->handle(new OrderPaid($order));

    expect(true)->toBeTrue();
});

test('listener handles OrderShipped without error', function () {
    $order = Order::create([
        'user_id' => $this->user->id, 'order_number' => 'WH-003', 'status' => 'shipped',
        'subtotal' => 50, 'total' => 50, 'shipping_cost' => 0, 'tax_amount' => 0, 'discount_amount' => 0,
        'tracking_number' => 'TRACK123',
    ]);

    $listener = new DispatchEcommerceWebhooks;
    $listener->handle(new OrderShipped($order));

    expect(true)->toBeTrue();
});

test('listener handles LowStockDetected without error', function () {
    $listener = new DispatchEcommerceWebhooks;
    $listener->handle(new LowStockDetected($this->variant));

    expect(true)->toBeTrue();
});

test('listener handles unknown event gracefully', function () {
    $listener = new DispatchEcommerceWebhooks;
    $listener->handle(new \stdClass);

    expect(true)->toBeTrue();
});
