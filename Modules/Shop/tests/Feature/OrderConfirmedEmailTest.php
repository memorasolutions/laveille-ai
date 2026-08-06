<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shop\Models\Order;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Art. 54.7 LPC : le courriel de confirmation de commande doit faire référence aux conditions
// de vente applicables (version + date) et tenir lieu de confirmation du contrat.
test('order confirmation email references the sales conditions version, date and link', function () {
    $order = Order::create([
        'email' => 'client@example.com',
        'status' => 'paid',
        'subtotal' => 42.00,
        'tax_amount' => 0,
        'shipping_cost' => 0,
        'total' => 42.00,
        'shipping_address' => ['first_name' => 'Client'],
    ]);

    $order->load('items.product');
    $trackingUrl = route('shop.order-lookup').'?email='.urlencode($order->email).'&order_id='.$order->id;

    $html = view('shop::emails.order-confirmed', [
        'order' => $order,
        'trackingUrl' => $trackingUrl,
    ])->render();

    $version = config('privacy.documents.sales_conditions.version');

    expect($html)->toContain('conditions de vente');
    expect($html)->toContain(url('/conditions-de-vente'));
    expect($html)->toContain($version);
    expect($html)->toContain('tient lieu de confirmation de votre contrat');
});
