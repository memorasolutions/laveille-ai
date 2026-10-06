<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Gelato\PrintFile;
use Modules\Shop\Gelato\PrintFileNotApprovedException;
use Modules\Shop\Gelato\PrintFileService;
use Modules\Shop\Gelato\PrintFileStatus;
use Modules\Shop\Listeners\CreateGelatoOrder;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderItem;
use Modules\Shop\Models\Product;

uses(Tests\TestCase::class, RefreshDatabase::class);

function zeProduct(): Product
{
    return Product::create(['name' => 'T-shirt', 'slug' => 't-'.uniqid(), 'price' => 30, 'status' => 'published']);
}

function zeOrder(Product $product): Order
{
    $order = Order::create([
        'email' => 'c@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30,
        'shipping_address' => ['first_name' => 'A', 'last_name' => 'B'],
    ]);
    OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30, 'gelato_variant_id' => 'UID-1']);

    return $order->fresh('items.product');
}

function zePrepared(Product $product, string $hash = 'aaa'): PrintFile
{
    return app(PrintFileService::class)->recordPrepared($product, [
        'contentHash' => $hash, 'publicUrl' => "https://cdn.test/{$hash}.png", 'mockupPreviewPath' => 'm.png',
        'validation' => ['effectiveDpi' => 300],
    ], 'UID-1');
}

function zeApprove(PrintFile $file): PrintFile
{
    $svc = app(PrintFileService::class);
    $svc->markMockupReady($file);

    return $svc->approve($file->fresh(), 1);
}

beforeEach(function () {
    config(['shop.gelato.api_key' => 'k', 'shop.gelato_zero_erreur' => true]);
});

test('double ShopOrderPaid ne crée qu une commande Gelato', function () {
    Http::fake(['order.gelatoapis.com/*' => Http::response(['id' => 'G-1'], 200)]);
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));
    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));
    expect(Order::find($order->id)->gelato_order_id)->toBe('G-1');
    // même sans gelato_order_id visible (course entre deux workers) : la clé atomique bloque
    Order::whereKey($order->id)->update(['gelato_order_id' => null]);
    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    Http::assertSentCount(1);
    Http::assertSent(fn ($r) => $r->hasHeader('Idempotency-Key')
        && $r['items'][0]['files'][0]['url'] === 'https://cdn.test/aaa.png'
        && ! isset($r['items'][0]['storeProductVariantId']));
});

test('sans print file approuvé : refus clair et aucun envoi Gelato', function () {
    Http::fake();
    $product = zeProduct();
    zePrepared($product); // PREPARED seulement
    $order = zeOrder($product);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));

    Http::assertNothingSent();
    expect(Order::find($order->id)->gelato_order_id)->toBeNull();
    expect(Order::find($order->id)->notes)->toContain('PRINT-FILE');
});

test('machine à états : impossible de sauter une étape ni de commander un non-APPROVED', function () {
    $product = zeProduct();
    $file = zePrepared($product);
    $svc = app(PrintFileService::class);

    expect(fn () => $svc->approve($file, 1))->toThrow(PrintFileNotApprovedException::class);
    expect(fn () => $svc->assertOrderable($product->id, 'UID-1'))->toThrow(PrintFileNotApprovedException::class);

    zeApprove($file);
    expect($svc->assertOrderable($product->id, 'UID-1')->status)->toBe(PrintFileStatus::Approved);
    expect($svc->markSellable($file->fresh())->status)->toBe(PrintFileStatus::Sellable);
    expect($svc->findOrderable($product->id, 'UID-1'))->not->toBeNull();
});

test('un changement de hash réinvalide l approbation', function () {
    $product = zeProduct();
    $file = zeApprove(zePrepared($product));
    expect($file->isOrderable())->toBeTrue();

    $again = zePrepared($product, 'bbb');

    expect($again->status)->toBe(PrintFileStatus::Prepared)
        ->and($again->approved_hash)->toBeNull()
        ->and(app(PrintFileService::class)->findOrderable($product->id, 'UID-1'))->toBeNull();

    // altération directe du hash en base : l'approbation ne correspond plus
    $ok = zeApprove($again);
    $ok->forceFill(['print_file_hash' => 'tampered'])->save();
    expect($ok->fresh()->isOrderable())->toBeFalse();
});

test('webhook Gelato fail-closed : sans secret configuré, sans en-tête ou mauvais secret = 401', function () {
    $payload = ['event' => 'order_status_updated', 'orderReferenceId' => 'X'];
    $url = route('shop.webhook.gelato');

    config(['shop.gelato_webhook_secret' => null]);
    $this->postJson($url, $payload)->assertStatus(401);

    config(['shop.gelato_webhook_secret' => 's3cret']);
    $this->postJson($url, $payload)->assertStatus(401);
    $this->postJson($url, $payload, ['X-Gelato-Secret' => 'mauvais'])->assertStatus(401);
    $this->postJson($url, $payload, ['X-Gelato-Secret' => 's3cret'])->assertOk();
});

test('webhook Stripe fail-closed : secret absent, sans signature ou signature invalide = 400', function () {
    $body = json_encode(['type' => 'ping']);
    $url = route('shop.webhook.stripe');
    $sign = fn (string $secret, ?int $t = null) => 't='.($t ??= time()).',v1='.hash_hmac('sha256', $t.'.'.$body, $secret);
    $post = fn (array $h) => $this->call('POST', $url, [], [], [], $h + ['CONTENT_TYPE' => 'application/json'], $body);

    config(['shop.stripe.webhook_secret' => null]);
    $post(['HTTP_STRIPE_SIGNATURE' => $sign('whsec')])->assertStatus(400);

    config(['shop.stripe.webhook_secret' => 'whsec']);
    $post([])->assertStatus(400);
    $post(['HTTP_STRIPE_SIGNATURE' => $sign('autre')])->assertStatus(400);
    $post(['HTTP_STRIPE_SIGNATURE' => $sign('whsec', time() - 4000)])->assertStatus(400);
    $post(['HTTP_STRIPE_SIGNATURE' => $sign('whsec')])->assertOk();
});

test('drapeau OFF : comportement historique intact (webhook Gelato sans secret accepté, Stripe 200)', function () {
    config(['shop.gelato_zero_erreur' => false, 'shop.gelato_webhook_secret' => null, 'shop.stripe.webhook_secret' => null]);

    $this->postJson(route('shop.webhook.gelato'), ['event' => 'x'])->assertOk();
    $this->postJson(route('shop.webhook.stripe'), [])->assertOk();
});
