<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Mockery;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Listeners\CreateGelatoOrder;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\GelatoService;

afterEach(function () {
    Mockery::close();
});

function makeOrderMock(array $items, int $id = 123): Order
{
    $order = Mockery::mock(Order::class)->makePartial();
    $order->setRawAttributes([
        'id' => $id,
        'order_number' => 'TEST-'.$id,
        'email' => 'client@example.com',
        'total' => '50.00',
        'currency' => 'CAD',
        'user_id' => null,
    ]);
    $order->setRelation('items', collect($items));

    return $order;
}

it('refuses order when item has empty gelato_variant_id', function () {
    Mail::fake();

    $product = (object) ['name' => 'T-shirt', 'metadata' => []];
    $item = (object) ['id' => 1, 'gelato_variant_id' => '', 'product' => $product];

    $order = makeOrderMock([$item]);
    $order->shouldReceive('update')
        ->withArgs(fn ($attrs) => str_contains($attrs['notes'] ?? '', 'variant_id_absent'))
        ->once()
        ->andReturnTrue();

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnTrue();
    $service->shouldNotReceive('createOrder');

    (new CreateGelatoOrder($service))->handle(new ShopOrderPaid($order));

    expect(true)->toBeTrue();
});

it('refuses order when product is null', function () {
    Mail::fake();

    $item = (object) ['id' => 1, 'gelato_variant_id' => 'var_abc', 'product' => null];

    $order = makeOrderMock([$item]);
    $order->shouldReceive('update')
        ->withArgs(fn ($attrs) => str_contains($attrs['notes'] ?? '', 'produit_introuvable'))
        ->once()
        ->andReturnTrue();

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnTrue();
    $service->shouldNotReceive('createOrder');

    (new CreateGelatoOrder($service))->handle(new ShopOrderPaid($order));

    expect(true)->toBeTrue();
});

it('refuses order when neither store_variant_map nor print_file_url present', function () {
    Mail::fake();

    $product = (object) [
        'name' => 'T-shirt',
        'metadata' => ['store_variant_map' => [], 'print_file_url' => null],
    ];
    $item = (object) ['id' => 1, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    $order = makeOrderMock([$item]);
    $order->shouldReceive('update')
        ->withArgs(fn ($attrs) => str_contains($attrs['notes'] ?? '', 'sans design'))
        ->once()
        ->andReturnTrue();

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnTrue();
    $service->shouldNotReceive('createOrder');

    (new CreateGelatoOrder($service))->handle(new ShopOrderPaid($order));

    expect(true)->toBeTrue();
});

it('accepts order when store_variant_map is present', function () {
    Mail::fake();

    $product = (object) [
        'name' => 'T-shirt',
        'metadata' => ['store_variant_map' => ['var_abc' => 'store_var_xyz']],
    ];
    $item = (object) ['id' => 1, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    $order = makeOrderMock([$item]);
    $order->shouldReceive('update')->once()->andReturnTrue();

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnTrue();
    $service->shouldReceive('createOrder')->once()->with($order)->andReturn('gelato-id-456');

    (new CreateGelatoOrder($service))->handle(new ShopOrderPaid($order));

    expect(true)->toBeTrue();
});

it('accepts order when print_file_url present (fallback)', function () {
    Mail::fake();

    $product = (object) [
        'name' => 'T-shirt',
        'metadata' => [
            'store_variant_map' => [],
            'print_file_url' => 'https://example.com/design.png',
        ],
    ];
    $item = (object) ['id' => 1, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    $order = makeOrderMock([$item]);
    $order->shouldReceive('update')->once()->andReturnTrue();

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnTrue();
    $service->shouldReceive('createOrder')->once()->with($order)->andReturn('gelato-id-789');

    (new CreateGelatoOrder($service))->handle(new ShopOrderPaid($order));

    expect(true)->toBeTrue();
});

it('skips entirely when service is not configured', function () {
    Mail::fake();

    $order = Mockery::mock(Order::class)->makePartial();
    $order->setRawAttributes(['id' => 999]);
    $order->shouldNotReceive('update');

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnFalse();
    $service->shouldNotReceive('createOrder');

    (new CreateGelatoOrder($service))->handle(new ShopOrderPaid($order));

    expect(true)->toBeTrue();
});
