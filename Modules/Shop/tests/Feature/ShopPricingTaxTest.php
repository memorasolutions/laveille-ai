<?php

/**
 * Prix zéro-perte (#3005) et taxes par destination (#3006) - spec docs/boutique-gelato-prix-taxes-2026.md
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\ExchangeRateService;
use Modules\Shop\Services\MarginGuard;
use Modules\Shop\Services\TaxCalculator;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::put('exchange_rate_usd_cad', 1.40, 3600);
    config(['shop.handling_fee' => 1.0, 'shop.tax.collect_pst' => false]);
});

// ---------------------------------------------------------------------------------------------------------------------
// Taxes par province
// ---------------------------------------------------------------------------------------------------------------------

dataset('provinces', [
    'ON TVH 13 %' => ['ON', 13.0, 'TVH (13 %)'],
    'NB TVH 15 %' => ['NB', 15.0, 'TVH (15 %)'],
    'NS TVH 14 %' => ['NS', 14.0, 'TVH (14 %)'],
    'PE TVH 15 %' => ['PE', 15.0, 'TVH (15 %)'],
    'NL TVH 15 %' => ['NL', 15.0, 'TVH (15 %)'],
    'QC TPS+TVQ 14,975 %' => ['QC', 14.975, 'TPS + TVQ'],
    'AB TPS 5 %' => ['AB', 5.0, 'TPS (5 %)'],
    'BC TPS seule' => ['BC', 5.0, 'TPS (5 %)'],
    'SK TPS seule' => ['SK', 5.0, 'TPS (5 %)'],
    'MB TPS seule' => ['MB', 5.0, 'TPS (5 %)'],
    'YT TPS 5 %' => ['YT', 5.0, 'TPS (5 %)'],
    'NT TPS 5 %' => ['NT', 5.0, 'TPS (5 %)'],
    'NU TPS 5 %' => ['NU', 5.0, 'TPS (5 %)'],
]);

test('taux exact par province, lignes = total au cent près', function (string $province, float $rate, string $label) {
    $calc = app(TaxCalculator::class)->compute('CA', $province, 100.0);

    expect($calc['total'])->toBe(round(100 * $rate / 100, 2))
        ->and($calc['label'])->toBe($label)
        ->and(round(array_sum(array_column($calc['lines'], 'amount')), 2))->toBe($calc['total']);
})->with('provinces');

test('somme des lignes = taxe facturée pour une base quelconque (pas d écart d arrondi)', function () {
    foreach (['QC', 'ON', 'BC'] as $province) {
        foreach ([0.99, 20.99, 33.49, 47.37, 123.45] as $base) {
            $calc = app(TaxCalculator::class)->compute('CA', $province, $base);
            expect(round(array_sum(array_column($calc['lines'], 'amount')), 2))->toBe($calc['total']);
        }
    }
});

test('hors Canada = 0 taxe', function () {
    expect(app(TaxCalculator::class)->compute('US', 'NY', 100.0)['total'])->toBe(0.0)
        ->and(app(TaxCalculator::class)->compute('FR', null, 100.0)['lines'])->toBe([]);
});

test('TVP provinciale NON perçue par défaut, perçue seulement si collect_pst = true', function () {
    expect(app(TaxCalculator::class)->compute('CA', 'BC', 100.0)['total'])->toBe(5.0);

    config(['shop.tax.collect_pst' => true]);
    $bc = app(TaxCalculator::class)->compute('CA', 'BC', 100.0);
    expect($bc['total'])->toBe(12.0)->and($bc['label'])->toBe('TPS + TVP');
    expect(app(TaxCalculator::class)->compute('CA', 'AB', 100.0)['total'])->toBe(5.0);
});

test('province absente du barème : inconnue pour le checkout; l estimation seule retombe sur la TPS', function () {
    expect(app(TaxCalculator::class)->isKnownProvince('ZZ'))->toBeFalse()->and(app(TaxCalculator::class)->isKnownProvince('on'))->toBeTrue();
    expect(app(TaxCalculator::class)->compute('CA', 'ZZ', 100.0)['total'])->toBe(5.0);
});

// ---------------------------------------------------------------------------------------------------------------------
// Checkout : taxe sur produit + livraison + manutention, garde-fou, borne pays
// ---------------------------------------------------------------------------------------------------------------------

function ptProduct(float $costBaseUsd = 8.0): Product
{
    return Product::create([
        'name' => 'T-shirt', 'slug' => 'pt-'.uniqid(), 'price' => 30, 'status' => 'published',
        'metadata' => ['gelato_store_product_id' => 'SP-1', 'store_variant_map' => ['UID-1' => 'SV-1'], 'print_file_url' => 'https://raw.test/f.png', 'cost_base' => $costBaseUsd],
    ]);
}

/** @return array{0: mixed, 1: Order|null, 2: int} réponse, commande, nombre d'appels Stripe */
function ptCheckout(Product $product, float $unitPrice, float $carrierPrice, array $address = [], int $expectedStripeCalls = 1): array
{
    config(['shop.handling_fee' => 1.0]);
    // Le devis renvoie le prix transporteur; le service ajoute la manutention (1 $) au prix de chaque mode.
    Http::fake(['order.gelatoapis.com/v4/orders:quote' => Http::response(['quotes' => [['shipmentMethods' => [['name' => 'Standard', 'shipmentMethodUid' => 'std', 'price' => $carrierPrice, 'currency' => 'CAD']]]]], 200)]);
    $shippingShown = round($carrierPrice + 1.0, 2);

    $stripeCalls = 0;
    $cart = Mockery::mock(\Modules\Shop\Services\CartService::class);
    $cart->shouldReceive('revalidatePrices')->andReturnTrue();
    $cart->shouldReceive('getCart')->andReturn((new \Modules\Shop\Models\Cart)->forceFill(['id' => 1]));
    $cart->shouldReceive('unavailableReason')->andReturnNull();
    $cart->shouldReceive('getContent')->andReturn([['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $unitPrice, 'gelato_variant_id' => 'UID-1', 'variant_label' => null]]);
    $cart->shouldReceive('subtotalOf')->andReturn($unitPrice);
    $cart->shouldReceive('clear')->andReturnNull();
    $stripe = Mockery::mock(\Modules\Shop\Services\StripeService::class);
    $stripe->shouldReceive('createCheckoutSession')->andReturnUsing(function () use (&$stripeCalls) {
        $stripeCalls++;

        return ['session_id' => 'cs_1', 'client_secret' => 'sec'];
    });

    $request = \Illuminate\Http\Request::create('/commander', 'POST', [
        'email' => 'c@example.com', 'shipping_cost' => $shippingShown,
        'shipping_address' => array_merge(['first_name' => 'A', 'last_name' => 'B', 'address_line1' => '1 rue X', 'city' => 'Laval', 'state' => 'QC', 'postal_code' => 'H7N1A1', 'country' => 'CA'], $address),
    ]);
    $request->setLaravelSession(app('session.store'));
    $response = (new \Modules\Shop\Http\Controllers\CheckoutController($cart, $stripe))->create($request);

    return [$response, Order::latest('id')->first(), $stripeCalls];
}

test('ON : la TVH 13 % porte sur produit + livraison + manutention (pas seulement le produit)', function () {
    [, $order, $stripe] = ptCheckout(ptProduct(), 40.0, 8.0, ['state' => 'ON', 'postal_code' => 'K1A 0B1']);

    // livraison affichée = 8 + 1 de manutention = 9
    expect($order)->not->toBeNull()
        ->and((float) $order->shipping_cost)->toBe(9.0)
        ->and((float) $order->tax_amount)->toBe(round((40.0 + 9.0) * 13 / 100, 2))
        ->and((float) $order->tax_amount)->not->toBe(round(40.0 * 13 / 100, 2))
        ->and(round((float) $order->total, 2))->toBe(round(40.0 + 9.0 + (float) $order->tax_amount, 2))
        ->and($stripe)->toBe(1);
    expect(array_sum(array_column($order->taxBreakdown(), 'amount')))->toEqualWithDelta((float) $order->tax_amount, 0.001);
});

test('QC : TPS + TVQ sur produit + livraison; la province du code postal fait foi', function () {
    [, $order] = ptCheckout(ptProduct(), 40.0, 8.0, ['state' => 'AB']);

    expect((float) $order->tax_amount)->toBe(round(49.0 * 14.975 / 100, 2))
        ->and($order->shipping_address['state'])->toBe('QC');
});

test('NS (B3H) : TVH 14 %', function () {
    [, $order] = ptCheckout(ptProduct(), 40.0, 8.0, ['state' => 'NS', 'postal_code' => 'B3H 1A1']);

    expect((float) $order->tax_amount)->toBe(round(49.0 * 14 / 100, 2));
});

test('garde-fou : une commande déficitaire est BLOQUÉE, aucune commande, AUCUN appel Stripe', function () {
    // coût 25 USD (35 CAD) vendu 30 $ : perte certaine
    [$response, $order, $stripe] = ptCheckout(ptProduct(25.0), 30.0, 8.0, [], 0);

    expect($order)->toBeNull()->and($stripe)->toBe(0)
        ->and(session('error'))->toContain('marge insuffisante');
});

test('garde-fou : commande saine passée (même cas, coût normal)', function () {
    [, $order, $stripe] = ptCheckout(ptProduct(8.0), 30.0, 8.0);

    expect($order)->not->toBeNull()->and($stripe)->toBe(1);
});

test('garde-fou : formule exacte (0,971 x total - 0,30 - taxe - coût >= plancher)', function () {
    $p = ptProduct(10.0); // coût 14,00 CAD au taux 1,40
    $guard = app(MarginGuard::class);
    $items = [['product_id' => $p->id, 'quantity' => 1]];

    // total 40, taxe 5, livraison payée 9 dont 1 de manutention => transporteur 8 ; C = 14 + 8 = 22
    // net = 0,971 x 40 - 0,30 - 5 - 22 = 11,54
    $r = $guard->evaluate($items, 40.0, 5.0, 9.0);
    expect($r['net'])->toBe(11.54)->and($r['ok'])->toBeTrue()->and($r['cost'])->toBe(22.0);

    // Plancher relevé au-dessus du net : bloqué
    config(['shop.pricing.floor_margin_cad' => 12.0]);
    expect($guard->evaluate($items, 40.0, 5.0, 9.0)['ok'])->toBeFalse();
});

test('garde-fou : coût inconnu = TOUJOURS bloqué, même drapeau désactivé', function () {
    $p = ptProduct(0.0);
    $items = [['product_id' => $p->id, 'quantity' => 1]];

    expect(app(MarginGuard::class)->evaluate($items, 100.0, 5.0, 9.0)['ok'])->toBeFalse();
    config(['shop.pricing.floor_block_unknown_cost' => false]);
    expect(app(MarginGuard::class)->evaluate($items, 100.0, 5.0, 9.0)['ok'])->toBeFalse();
});

test('garde-fou : net juste SOUS le plancher (-0,004 $) refusé, jamais arrondi à zéro', function () {
    config(['shop.pricing.floor_margin_cad' => 0.0]);
    $p = ptProduct(10.0); // 14,00 CAD
    $items = [['product_id' => $p->id, 'quantity' => 1]];
    // net = 0,971 x total - 0,30 - 5 - 22 (livraison 9 dont 1 manutention) ; total = (27,3 + 0,004)/0,971 => net = 0 exactement
    $total = (22 + 5 + 0.30) / 0.971;
    $just = round($total - 0.004, 6);

    expect(app(MarginGuard::class)->evaluate($items, $just, 5.0, 9.0)['ok'])->toBeFalse();
    expect(app(MarginGuard::class)->evaluate($items, round($total + 0.01, 6), 5.0, 9.0)['ok'])->toBeTrue();
});

test('plancher par défaut strictement positif (1 $)', function () {
    expect(config('shop.pricing.floor_margin_cad'))->toBe(1.0);
});

test('garde-fou : le coût de la VARIANTE commandée est utilisé (grande taille plus chère)', function () {
    $p = ptProduct(8.0);
    $p->update(['metadata' => array_merge($p->metadata, ['cost_by_uid' => ['UID-M' => 8.0, 'UID-3XL' => 20.0]])]);
    $guard = app(MarginGuard::class);

    $m = $guard->evaluate([['product_id' => $p->id, 'quantity' => 1, 'gelato_variant_id' => 'UID-M']], 40.0, 5.0, 9.0);
    $xl = $guard->evaluate([['product_id' => $p->id, 'quantity' => 1, 'gelato_variant_id' => 'UID-3XL']], 40.0, 5.0, 9.0);
    $unknown = $guard->evaluate([['product_id' => $p->id, 'quantity' => 1, 'gelato_variant_id' => 'UID-?']], 40.0, 5.0, 9.0);

    expect($m['cost'])->toBe(19.2)->and($m['ok'])->toBeTrue()
        ->and($xl['cost'])->toBe(36.0)->and($xl['ok'])->toBeFalse()   // 28 + 8 : perte que cost_base seul ne voyait pas
        ->and($unknown['cost'])->toBe(36.0);                          // uid inconnu = MAX connu
});

test('checkout : province CA non résolue refusée (adresse à préciser), aucune commande', function () {
    config(['shop.tax.provinces' => ['QC' => ['qst' => true]]]); // ON retiré du barème : simule une province non résolue
    [, $order, $stripe] = ptCheckout(ptProduct(), 40.0, 8.0, ['state' => 'ON', 'postal_code' => 'K1A 0B1'], 0);

    expect($order)->toBeNull()->and($stripe)->toBe(0)
        ->and(session('errors')->get('shipping_address.postal_code'))->not->toBeEmpty();
});

test('borne pays : un pays hors shipping_countries est rejeté proprement, aucune commande', function () {
    [$response, $order, $stripe] = ptCheckout(ptProduct(), 40.0, 8.0, ['country' => 'US', 'state' => 'NY', 'postal_code' => '10001'], 0);

    expect($order)->toBeNull()->and($stripe)->toBe(0)
        ->and(session('errors')->get('shipping_address.country'))->not->toBeEmpty();
});

test('borne pays : pays ouvert par config accepté, 0 taxe canadienne', function () {
    config(['shop.shipping_countries' => ['CA', 'US']]);
    [, $order] = ptCheckout(ptProduct(), 40.0, 8.0, ['country' => 'US', 'state' => 'NY', 'postal_code' => '10001']);

    expect($order)->not->toBeNull()->and((float) $order->tax_amount)->toBe(0.0);
});

test('borne pays : code en minuscules normalisé (ca) et toujours taxé', function () {
    [, $order] = ptCheckout(ptProduct(), 40.0, 8.0, ['country' => 'ca']);

    expect($order)->not->toBeNull()->and((float) $order->tax_amount)->toBeGreaterThan(0.0);
});

// ---------------------------------------------------------------------------------------------------------------------
// Prix : gross-up Stripe, arrondi vers le haut, taux de change
// ---------------------------------------------------------------------------------------------------------------------

test('le prix produit absorbe les 2,9 % Stripe : (1 + marge) / (1 - 0,029)', function () {
    $cost = 13.0;
    $noGrossUp = Product::roundTo99($cost * 1.40 * 1.30);
    $expected = Product::roundTo99($cost * 1.40 * 1.30 / (1 - 0.029));

    expect(Product::smartPrice($cost, 't-shirts'))->toBe($expected)
        ->and($expected)->toBeGreaterThan($noGrossUp);

    // Après Stripe (2,9 % du prix seul), la marge réelle sur le produit reste >= la marge visée
    $price = Product::smartPrice($cost, 't-shirts');
    expect($price * (1 - 0.029))->toBeGreaterThanOrEqual($cost * 1.40 * 1.30);
});

test('arrondi au .99 vers le HAUT, jamais sous le prix calculé', function () {
    expect(Product::roundTo99(54.24))->toBe(54.99)
        ->and(Product::roundTo99(54.99))->toBe(54.99)
        ->and(Product::roundTo99(55.00))->toBe(55.99)
        ->and(Product::roundTo99(55.04))->toBe(55.99)
        ->and(Product::roundTo99(54.9901))->toBe(55.99)
        ->and(Product::roundTo99(0.50))->toBe(0.99);

    foreach ([12.3, 19.0, 19.98, 19.99, 20.0, 33.33, 99.5] as $p) {
        expect(Product::roundTo99($p))->toBeGreaterThanOrEqual($p);
    }
});

test('bug FX corrigé : le repli lit shop.pricing.usd_cad_rate (SHOP_USD_CAD_RATE n est plus ignoré)', function () {
    Cache::forget('exchange_rate_usd_cad');
    Http::fake(['api.frankfurter.app/*' => Http::response('down', 500)]);
    config(['shop.pricing.usd_cad_rate' => 1.55, 'shop.pricing.usd_cad_rate_floor' => 1.40]);

    expect(ExchangeRateService::rate())->toBe(1.55);
});

test('plancher de taux : un taux vivant plus bas (CAD fort) est relevé au plancher; un taux plus haut est suivi', function () {
    config(['shop.pricing.usd_cad_rate_floor' => 1.40]);

    Cache::put('exchange_rate_usd_cad', 1.30, 3600);
    expect(ExchangeRateService::rate())->toBe(1.40);

    Cache::put('exchange_rate_usd_cad', 1.52, 3600);
    expect(ExchangeRateService::rate())->toBe(1.52);
});

test('un taux d API nul n est jamais mis en cache (prix à 0 $)', function () {
    Cache::forget('exchange_rate_usd_cad');
    Http::fake(['api.frankfurter.app/*' => Http::response(['rates' => ['CAD' => 0]], 200)]);
    config(['shop.pricing.usd_cad_rate' => 1.45]);

    expect(ExchangeRateService::rate())->toBe(1.45)->and(Cache::get('exchange_rate_usd_cad'))->toBeNull();
});
