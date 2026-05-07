<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\Shop\Services\GelatoService;
use ReflectionClass;

function invokeBuildPayload(GelatoService $service, object $item): array
{
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('buildOrderItemPayload');
    $method->setAccessible(true);

    return $method->invokeArgs($service, [$item]);
}

it('uses storeProductVariantId when store_variant_map maps the gelato_variant_id', function () {
    $product = (object) [
        'metadata' => ['store_variant_map' => ['var_abc' => 'store_var_xyz']],
        'id' => 9,
    ];
    $item = (object) [
        'id' => 1,
        'quantity' => 2,
        'gelato_variant_id' => 'var_abc',
        'product' => $product,
    ];

    $payload = invokeBuildPayload(new GelatoService(), $item);

    expect($payload)->toBe([
        'itemReferenceId' => '1',
        'storeProductVariantId' => 'store_var_xyz',
        'quantity' => 2,
    ])
        ->and($payload)->not->toHaveKey('productUid')
        ->and($payload)->not->toHaveKey('files');
});

it('falls back to productUid + files when only print_file_url is set', function () {
    $product = (object) [
        'metadata' => [
            'store_variant_map' => [],
            'print_file_url' => 'https://example.com/design.png',
        ],
        'id' => 9,
    ];
    $item = (object) [
        'id' => 5,
        'quantity' => 3,
        'gelato_variant_id' => 'var_abc',
        'product' => $product,
    ];

    $payload = invokeBuildPayload(new GelatoService(), $item);

    expect($payload)->toMatchArray([
        'itemReferenceId' => '5',
        'productUid' => 'var_abc',
        'quantity' => 3,
        'files' => [['type' => 'default', 'url' => 'https://example.com/design.png']],
    ])
        ->and($payload)->not->toHaveKey('storeProductVariantId');
});

it('falls back to productUid without files when no print_file_url present', function () {
    $product = (object) [
        'metadata' => ['store_variant_map' => []],
        'id' => 9,
    ];
    $item = (object) [
        'id' => 7,
        'quantity' => 1,
        'gelato_variant_id' => 'var_abc',
        'product' => $product,
    ];

    $payload = invokeBuildPayload(new GelatoService(), $item);

    expect($payload)->toBe([
        'itemReferenceId' => '7',
        'productUid' => 'var_abc',
        'quantity' => 1,
    ])
        ->and($payload)->not->toHaveKey('files')
        ->and($payload)->not->toHaveKey('storeProductVariantId');
});
