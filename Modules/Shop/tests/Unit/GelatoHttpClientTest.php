<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Gelato\GelatoHttpClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    config(['shop.gelato.api_key' => 'TEST_KEY', 'shop.gelato.api_url' => 'https://api.example.test']);
});

dataset('hotes gelato', [
    'base' => ['base', 'https://api.example.test'],
    'order' => ['order', 'https://order.gelatoapis.com'],
    'product' => ['product', 'https://product.gelatoapis.com'],
    'ecommerce' => ['ecommerce', 'https://ecommerce.gelatoapis.com'],
]);

it('cible le bon hôte avec l\'en-tête X-API-KEY', function (string $api, string $host) {
    Http::fake();

    GelatoHttpClient::forApi($api)->get('/sonde');

    Http::assertSent(fn (Request $r) => $r->hasHeader('X-API-KEY', 'TEST_KEY')
        && $r->url() === $host.'/sonde');
})->with('hotes gelato');

it('conserve une URL absolue telle quelle', function () {
    Http::fake();

    GelatoHttpClient::forApi('product')->get('https://product.gelatoapis.com/v3/products/x/prices', ['country' => 'CA']);

    Http::assertSent(fn (Request $r) => $r->hasHeader('X-API-KEY', 'TEST_KEY')
        && str_starts_with($r->url(), 'https://product.gelatoapis.com/v3/products/x/prices'));
});

it('lève une exception pour une API inconnue', function () {
    GelatoHttpClient::forApi('zzz');
})->throws(InvalidArgumentException::class, 'API Gelato inconnue : zzz');
