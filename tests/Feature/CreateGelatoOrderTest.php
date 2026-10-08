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

it('refuses a catalogue product without store mapping, even if print_file_url is set', function () {
    Mail::fake();

    $product = (object) [
        'name' => 'T-shirt',
        'metadata' => ['gelato_store_product_id' => 'sp_1', 'store_variant_map' => [], 'print_file_url' => 'https://example.com/design.png'],
    ];
    $item = (object) ['id' => 1, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    $order = makeOrderMock([$item]);
    $order->shouldReceive('update')
        ->withArgs(fn ($attrs) => str_contains($attrs['notes'] ?? '', 'sans storeProductVariantId'))
        ->once()
        ->andReturnTrue();

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnTrue();
    $service->shouldNotReceive('createOrder');

    (new CreateGelatoOrder($service))->handle(new ShopOrderPaid($order));

    expect(true)->toBeTrue();
});

// Le chemin « commande acceptée » (mapping présent) passe désormais par le verrou atomique en base (même verrou que
// le chemin strict) : il ne peut plus se tester avec un Order simulé. Couvert avec une vraie base par
// Modules/Shop/tests/Feature/GelatoZeroErreurTest.php : « flag OFF : le routage par produit reste appliqué » et « Z1 ... ».

it('refuses an editor product that only has a raw print_file_url (no approved PrintFile)', function () {
    Mail::fake();

    $printFiles = Mockery::mock(\Modules\Shop\Gelato\PrintFileService::class);
    $printFiles->shouldReceive('assertOrderable')->andThrow(new \Modules\Shop\Gelato\PrintFileNotApprovedException('Aucun fichier approuvé'));
    app()->instance(\Modules\Shop\Gelato\PrintFileService::class, $printFiles);

    $product = (object) [
        'id' => 4,
        'name' => 'T-shirt',
        'metadata' => ['store_variant_map' => [], 'print_file_url' => 'https://example.com/design.png'],
    ];
    $item = (object) ['id' => 1, 'product_id' => 4, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    $order = makeOrderMock([$item]);
    $order->shouldReceive('update')
        ->withArgs(fn ($attrs) => str_contains($attrs['notes'] ?? '', 'approuvé'))
        ->once()
        ->andReturnTrue();

    $service = Mockery::mock(GelatoService::class);
    $service->shouldReceive('isConfigured')->andReturnTrue();
    $service->shouldNotReceive('createOrder');

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
