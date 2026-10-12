<?php

/**
 * Garde-fou de marge : fail-closed sur une devise de vente non-CAD et sur un coût inconnu, acceptation d'un panier sain.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Shop\Services\MarginGuard;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('devise de vente non-CAD : commande refusée (fail-closed, avant tout calcul)', function () {
    config(['shop.currency' => 'EUR']);

    $res = app(MarginGuard::class)->evaluate([['product_id' => 1, 'quantity' => 1]], 100.0, 0.0, 0.0);

    expect($res['ok'])->toBeFalse()
        ->and($res['reason'])->toContain('devise');
});

test('coût inconnu : commande bloquée (jamais de vente sans coût)', function () {
    config(['shop.currency' => 'CAD', 'shop.pricing.floor_block_unknown_cost' => true]);
    Cache::put('exchange_rate_usd_cad', 1.40, 3600);

    $res = app(MarginGuard::class)->evaluate(
        [['product_id' => 999999, 'quantity' => 1, 'gelato_variant_id' => 'x']],
        100.0,
        0.0,
        0.0
    );

    expect($res['ok'])->toBeFalse();
});

test('panier sain en CAD : accepté (le garde ne bloque pas par défaut)', function () {
    config(['shop.currency' => 'CAD', 'shop.pricing.floor_margin_cad' => 1.0, 'shop.handling_fee' => 0.0]);
    Cache::put('exchange_rate_usd_cad', 1.40, 3600);

    // Aucun article à coûter (panier vide), transport 5 CAD, total 100 CAD : marge nette largement positive.
    $res = app(MarginGuard::class)->evaluate([], 100.0, 0.0, 5.0);

    expect($res['ok'])->toBeTrue();
});
