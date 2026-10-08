<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\Shop\Gelato\GelatoRoutingException;
use Modules\Shop\Gelato\OrderItemRouter;
use Modules\Shop\Gelato\PrintFileNotApprovedException;
use Modules\Shop\Gelato\PrintFileService;
use Mockery;

afterEach(fn () => Mockery::close());

function routerWith(?PrintFileService $printFiles = null): OrderItemRouter
{
    return new OrderItemRouter($printFiles ?? Mockery::mock(PrintFileService::class));
}

it('catalogue : storeProductVariantId via store_variant_map, aucune clé files', function () {
    $product = (object) ['id' => 9, 'metadata' => ['gelato_store_product_id' => 'sp', 'store_variant_map' => ['var_abc' => 'store_var_xyz']]];
    $item = (object) ['id' => 1, 'quantity' => 2, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    $payload = routerWith()->resolvePayload($item);

    expect($payload)->toBe(['itemReferenceId' => '1', 'storeProductVariantId' => 'store_var_xyz', 'quantity' => 2])
        ->and($payload)->not->toHaveKey('files')
        ->and($payload)->not->toHaveKey('productUid');
});

it('catalogue sans mapping : exception typée, jamais de repli sur print_file_url', function () {
    $product = (object) ['id' => 9, 'metadata' => ['gelato_store_product_id' => 'sp', 'store_variant_map' => [], 'print_file_url' => 'https://example.com/raw.png']];
    $item = (object) ['id' => 5, 'quantity' => 3, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    expect(fn () => routerWith()->resolvePayload($item))->toThrow(GelatoRoutingException::class, 'storeProductVariantId');
    expect(routerWith()->validate($item))->toContain('storeProductVariantId');
});

it('éditeur : productUid + fichier approuvé, print_file_url brut ignoré', function () {
    $printFiles = Mockery::mock(PrintFileService::class);
    $printFiles->shouldReceive('assertOrderable')->once()->with(9, 'var_abc', 'var_abc')
        ->andReturn((new \Modules\Shop\Gelato\PrintFile)->forceFill(['public_url' => 'https://cdn.test/approved.png']));
    $product = (object) ['id' => 9, 'metadata' => ['print_file_url' => 'https://example.com/raw.png']];
    $item = (object) ['id' => 7, 'product_id' => 9, 'quantity' => 1, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    $payload = routerWith($printFiles)->resolvePayload($item);

    expect($payload)->toBe([
        'itemReferenceId' => '7', 'productUid' => 'var_abc', 'quantity' => 1,
        'files' => [['type' => 'default', 'url' => 'https://cdn.test/approved.png']],
    ])->and(json_encode($payload))->not->toContain('raw.png');
});

it('éditeur sans fichier approuvé : validate renvoie la raison, resolvePayload lève', function () {
    $printFiles = Mockery::mock(PrintFileService::class);
    $printFiles->shouldReceive('assertOrderable')->andThrow(new PrintFileNotApprovedException('Aucun fichier approuvé'));
    $product = (object) ['id' => 9, 'metadata' => ['print_file_url' => 'https://example.com/raw.png']];
    $item = (object) ['id' => 7, 'product_id' => 9, 'quantity' => 1, 'gelato_variant_id' => 'var_abc', 'product' => $product];

    expect(routerWith($printFiles)->validate($item))->toContain('approuvé');
    expect(fn () => routerWith($printFiles)->resolvePayload($item))->toThrow(PrintFileNotApprovedException::class);
});

it('commandable : validate renvoie null', function () {
    $product = (object) ['id' => 9, 'metadata' => ['gelato_store_product_id' => 'sp', 'store_variant_map' => ['v' => 's']]];

    expect(routerWith()->validate((object) ['id' => 1, 'quantity' => 1, 'gelato_variant_id' => 'v', 'product' => $product]))->toBeNull();
});
