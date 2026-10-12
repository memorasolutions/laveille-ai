<?php

/**
 * Aperçu de taxe du panier : une seule source (TaxCalculator), un seul arrondi, identique au chargement et en AJAX.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shop\Http\Controllers\CartController;
use Modules\Shop\Services\CartService;
use Modules\Shop\Services\TaxCalculator;

uses(Tests\TestCase::class, RefreshDatabase::class);

function cartWithSubtotal(float $subtotal): void
{
    config(['shop.maintenance' => false, 'shop.founder_only' => false]);
    $cart = Mockery::mock(CartService::class)->makePartial();
    $cart->shouldReceive('getSubtotal')->andReturn($subtotal);
    $cart->shouldReceive('getContent')->andReturn([[
        'product_id' => 1, 'quantity' => 1, 'unit_price' => $subtotal, 'variant_label' => null, 'product_name' => 'Test',
        'product_slug' => null, 'product_images' => [], 'product_variants' => [], 'product_sizes' => [], 'product_category' => null,
    ]]);
    $cart->shouldReceive('itemCount')->andReturn(0);
    app()->instance(CartService::class, $cart);
}

dataset('sous-totaux', [29.99, 34.99, 59.98, 17.33, 100.01, 0.07]);

test('la taxe passée à la vue du panier = TaxCalculator::compute QC, sans double arrondi', function (float $subtotal) {
    cartWithSubtotal($subtotal);
    $expected = app(TaxCalculator::class)->compute('CA', 'QC', $subtotal);

    $response = $this->get(route('shop.cart'))->assertOk();

    expect($response->viewData('tax'))->toBe($expected['total'])
        ->and($response->viewData('cartTps'))->toBe($expected['lines'][0]['amount'])
        ->and($response->viewData('cartTvq'))->toBe($expected['lines'][1]['amount'])
        ->and(round($response->viewData('cartTps') + $response->viewData('cartTvq'), 2))->toBe($expected['total'])
        ->and($response->viewData('total'))->toBe(round($subtotal + $expected['total'], 2));
})->with('sous-totaux');

test('chargement initial et mise à jour AJAX donnent exactement les mêmes montants', function (float $subtotal) {
    cartWithSubtotal($subtotal);

    $initial = $this->get(route('shop.cart'))->assertOk();
    $m = new ReflectionMethod(CartController::class, 'cartTotals');
    $ajax = $m->invoke(app(CartController::class), 'CA', 'QC');

    expect($initial->viewData('cartTps'))->toBe($ajax['tps'])
        ->and($initial->viewData('cartTvq'))->toBe($ajax['tvq'])
        ->and($initial->viewData('tax'))->toBe($ajax['tax'])
        ->and($initial->viewData('total'))->toBe($ajax['total']);
})->with('sous-totaux');

test('la vue porte le libellé d estimation et plus de calcul round() côté Blade', function () {
    cartWithSubtotal(29.99);
    $this->get(route('shop.cart'))->assertSee('Taxes estimées (Québec)', false)
        ->assertSee('sont calculées à l&#039;étape de paiement.', false);

    expect(file_get_contents(base_path('Modules/Shop/resources/views/public/cart.blade.php')))
        ->not->toContain("round(\$subtotal * config('shop.tax");
});
