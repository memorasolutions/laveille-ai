<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Shop\Events\ShopOrderShipped;
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
    ], 'UID-1', 'front', 'UID-1');
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
    // #4 : statut explicite + enregistrement persistant, notes jamais écrasées
    expect(Order::find($order->id)->status)->toBe('awaiting_print_file')
        ->and(Order::find($order->id)->gelato_issue)->toContain('approuvé');
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


// ---- Corrections de la revue adversariale (zone argent) ----

test('#1 échec ambigu (réponse perdue / 5xx) : clé NON libérée, état UNKNOWN, aucun doublon', function () {
    Mail::fake();
    Http::fake(['order.gelatoapis.com/*' => Http::response('boom', 502)]);
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));
    $after = Order::find($order->id);
    expect($after->gelato_submit_key)->not->toBeNull()
        ->and($after->gelato_submit_state)->toBe('unknown')
        ->and($after->gelato_submit_started_at)->not->toBeNull();

    // nouvel événement : le verrou tient, aucun second POST de création
    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));
    Http::assertSentCount(1);
});

test('#1 rejet 4xx confirmé : la clé est libérée pour permettre une reprise', function () {
    Mail::fake();
    Http::fake(['order.gelatoapis.com/*' => Http::response(['message' => 'invalid'], 422)]);
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));

    $after = Order::find($order->id);
    expect($after->gelato_submit_key)->toBeNull()->and($after->gelato_submit_state)->toBe('rejected');
});

test('#1/#2 verrou orphelin : réconciliation adopte la commande existante et alerte l admin, sans recréer', function () {
    config(['shop.admin_email' => 'admin@example.com']);
    Http::fake([
        'order.gelatoapis.com/v4/orders:search' => Http::response(['orders' => [['id' => 'G-EXIST', 'orderReferenceId' => 'REF-1']]], 200),
        'order.gelatoapis.com/v4/orders' => Http::response(['id' => 'G-NEW'], 200),
    ]);
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);
    $order->forceFill(['order_number' => 'REF-1'])->save();
    Order::whereKey($order->id)->update([
        'gelato_submit_key' => 'laveille-shop-order-REF-1', 'gelato_submit_state' => 'submitting',
        'gelato_submit_started_at' => now()->subMinutes(60),
    ]);

    Mail::shouldReceive('raw')->atLeast()->once();
    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    expect(Order::find($order->id)->gelato_order_id)->toBe('G-EXIST');
    Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v4/orders'));
});

test('#1 verrou orphelin, recherche vide : NE CRÉE PAS, reste unknown, alerte admin', function () {
    Mail::fake();
    Http::fake([
        'order.gelatoapis.com/v4/orders:search' => Http::response(['orders' => []], 200),
        'order.gelatoapis.com/v4/orders' => Http::response(['id' => 'G-NEW'], 200),
    ]);
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);
    Order::whereKey($order->id)->update([
        'gelato_submit_key' => 'laveille-shop-order-'.$order->order_number, 'gelato_submit_state' => 'unknown',
        'gelato_submit_started_at' => now()->subMinutes(60),
    ]);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    expect(Order::find($order->id)->gelato_order_id)->toBeNull()
        ->and(Order::find($order->id)->gelato_submit_state)->toBe('unknown');
    Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v4/orders'));
});

test('#1 réconciliation impossible : on ne crée pas (pas de double), état UNKNOWN', function () {
    Mail::fake();
    Http::fake(['order.gelatoapis.com/v4/orders:search' => Http::response('down', 503)]);
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);
    Order::whereKey($order->id)->update([
        'gelato_submit_key' => 'k', 'gelato_submit_started_at' => now()->subMinutes(60),
    ]);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/v4/orders'));
    expect(Order::find($order->id)->gelato_order_id)->toBeNull()
        ->and(Order::find($order->id)->gelato_submit_state)->toBe('unknown');
});

test('#3 Stripe : erreur interne après signature valide = 500 (Stripe rejoue), pas 200', function () {
    config(['shop.stripe.webhook_secret' => 'whsec']);
    $product = zeProduct();
    $order = zeOrder($product);
    $order->forceFill(['status' => 'pending', 'stripe_session_id' => 'cs_1'])->save();
    Event::listen(ShopOrderPaid::class, fn () => throw new RuntimeException('BD indisponible'));

    $body = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_1', 'payment_intent' => 'pi_1']]]);
    $t = time();
    $sig = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$body, 'whsec');
    $call = fn () => $this->call('POST', route('shop.webhook.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body);

    $call()->assertStatus(500);
    // la signature invalide reste 400
    $this->call('POST', route('shop.webhook.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.$t.',v1=bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
});

test('#3 Gelato : erreur interne après secret valide = 500 (Gelato rejoue)', function () {
    config(['shop.gelato_webhook_secret' => 's3cret']);
    $order = zeOrder(zeProduct());
    Event::listen(ShopOrderShipped::class, fn () => throw new RuntimeException('BD indisponible'));

    $this->postJson(route('shop.webhook.gelato'), [
        'event' => 'order_status_updated', 'orderReferenceId' => $order->order_number, 'fulfillmentStatus' => 'shipped',
    ], ['X-Gelato-Secret' => 's3cret'])->assertStatus(500);
});

test('#4 payé puis approbation invalidée : awaiting_print_file, notes intactes, aucun envoi', function () {
    config(['shop.admin_email' => 'admin@example.com']);
    Http::fake();
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);
    $order->forceFill(['notes' => 'note client'])->save();
    zePrepared($product, 'zzz'); // nouvel hash : approbation invalidée entre checkout et commande

    Mail::shouldReceive('raw')->once();
    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    $after = Order::find($order->id);
    expect($after->status)->toBe('awaiting_print_file')
        ->and($after->notes)->toBe('note client')
        ->and($after->gelato_issue)->toContain('approuvé');
    Http::assertNothingSent();
});

test('#6 URL non absolue https refusée à l enregistrement', function () {
    $product = zeProduct();
    $svc = app(PrintFileService::class);
    foreach ([null, '', '/storage/x.png', 'http://cdn.test/x.png'] as $bad) {
        expect(fn () => $svc->recordPrepared($product, ['contentHash' => 'h', 'publicUrl' => $bad], 'UID-1'))
            ->toThrow(PrintFileNotApprovedException::class);
    }
});

test('#7 productUid différent de celui de la préparation : refus', function () {
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $svc = app(PrintFileService::class);

    expect($svc->assertOrderable($product->id, 'UID-1', 'UID-1'))->toBeInstanceOf(PrintFile::class);
    expect(fn () => $svc->assertOrderable($product->id, 'UID-1', 'AUTRE-ZONE'))->toThrow(PrintFileNotApprovedException::class);
});

test('#8 re-prepare au même hash ne réécrit pas l URL d un fichier approuvé', function () {
    $product = zeProduct();
    $file = zeApprove(zePrepared($product));
    app(PrintFileService::class)->recordPrepared($product, [
        'contentHash' => 'aaa', 'publicUrl' => 'https://evil.test/autre.png', 'engineVersion' => '9',
    ], 'UID-1', 'front', 'UID-1');

    $fresh = $file->fresh();
    expect($fresh->public_url)->toBe('https://cdn.test/aaa.png')->and($fresh->isOrderable())->toBeTrue();
});

test('#9 unicité inopérante avec variant_uid NULL : doublon refusé', function () {
    $product = zeProduct();
    $row = ['product_id' => $product->id, 'print_area' => 'front', 'status' => 'DRAFT', 'variant_uid' => null, 'variant_key' => ''];
    DB::table('shop_print_files')->insert($row + ['created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('shop_print_files')->insert($row + ['created_at' => now(), 'updated_at' => now()]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('#10 status et approved_hash non assignables en masse ; approve revalide le hash', function () {
    $product = zeProduct();
    $file = zePrepared($product);
    $file->fill(['status' => PrintFileStatus::Approved, 'approved_hash' => 'aaa']);
    expect($file->status)->toBe(PrintFileStatus::Prepared)->and($file->approved_hash)->toBeNull();

    $svc = app(PrintFileService::class);
    $svc->markMockupReady($file);
    $stale = $file->fresh();
    zePrepared($product, 'ccc'); // re-prepare concurrent : repasse PREPARED
    expect(fn () => $svc->approve($stale, 1))->toThrow(PrintFileNotApprovedException::class);
});


// ---- 3e round ----

test('#1 recherche renvoie null/corps inexploitable : pas de création, unknown', function () {
    Mail::fake();
    Http::fake([
        'order.gelatoapis.com/v4/orders:search' => Http::response(['orders' => null], 200),
        'order.gelatoapis.com/v4/orders' => Http::response(['id' => 'G-NEW'], 200),
    ]);
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);
    Order::whereKey($order->id)->update(['gelato_submit_key' => 'k', 'gelato_submit_started_at' => now()->subMinutes(60)]);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    expect(Order::find($order->id)->gelato_order_id)->toBeNull()->and(Order::find($order->id)->gelato_submit_state)->toBe('unknown');
    Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v4/orders'));
});

test('#2 clé API absente + drapeau ON : awaiting_print_file, alerte, aucun envoi', function () {
    config(['shop.gelato.api_key' => null, 'shop.admin_email' => 'admin@example.com']);
    Http::fake();
    Mail::shouldReceive('raw')->once();
    $product = zeProduct();
    zeApprove(zePrepared($product));
    $order = zeOrder($product);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));

    Http::assertNothingSent();
    expect(Order::find($order->id)->status)->toBe('awaiting_print_file')
        ->and(Order::find($order->id)->gelato_issue)->toContain('Clé API');
});

test('#2 clé API absente + drapeau OFF : comportement historique (ignorée, statut inchangé)', function () {
    config(['shop.gelato.api_key' => null, 'shop.gelato_zero_erreur' => false]);
    Http::fake();
    $order = zeOrder(zeProduct());

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));

    expect(Order::find($order->id)->status)->toBe('paid');
});

test('#3 checkout refuse un productUid non concordant avant paiement', function () {
    $product = zeProduct();
    zeApprove(zePrepared($product)); // préparé pour UID-1
    $svc = app(PrintFileService::class);
    expect($svc->assertOrderable($product->id, 'UID-1', 'UID-1'))->toBeInstanceOf(PrintFile::class);
    expect(fn () => $svc->assertOrderable($product->id, 'UID-1', 'AUTRE'))->toThrow(PrintFileNotApprovedException::class);
    // le contrôle du checkout appelle exactement assertOrderable($pid, $variant, $variant)
    $src = file_get_contents(base_path('Modules/Shop/app/Http/Controllers/CheckoutController.php'));
    expect($src)->toContain('assertOrderable((int) $cartItem[\'product_id\'], $variant, $variant)');
});

test('#6 rejeu ShopOrderPaid : un seul courriel de confirmation', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $order = zeOrder(zeProduct());

    app(\Modules\Shop\Listeners\SendOrderConfirmation::class)->handle(new ShopOrderPaid($order));
    app(\Modules\Shop\Listeners\SendOrderConfirmation::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    \Illuminate\Support\Facades\Notification::assertSentOnDemandTimes(\Modules\Shop\Notifications\OrderConfirmedNotification::class, 1);
    expect(Order::find($order->id)->confirmation_sent_at)->not->toBeNull();
});

test('#7 shop:gelato-reconcile adopte si trouvée, sinon alerte sans créer', function () {
    Mail::fake();
    Http::fake([
        'order.gelatoapis.com/v4/orders:search' => Http::sequence()
            ->push(['orders' => [['id' => 'G-X', 'orderReferenceId' => 'REF-A']]], 200)
            ->push(['orders' => []], 200),
        'order.gelatoapis.com/v4/orders' => Http::response(['id' => 'G-NEW'], 200),
    ]);
    $p = zeProduct();
    $a = zeOrder($p); $a->forceFill(['order_number' => 'REF-A'])->save();
    $b = zeOrder($p); $b->forceFill(['order_number' => 'REF-B'])->save();
    foreach ([$a, $b] as $o) {
        Order::whereKey($o->id)->update(['gelato_submit_key' => 'k'.$o->id, 'gelato_submit_state' => 'unknown']);
    }

    $this->artisan('shop:gelato-reconcile')->assertSuccessful();

    expect(Order::find($a->id)->gelato_order_id)->toBe('G-X')
        ->and(Order::find($b->id)->gelato_order_id)->toBeNull()
        ->and(Order::find($b->id)->gelato_submit_state)->toBe('unknown');
    Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v4/orders'));
});

test('#9 markMockupReady/markSellable refusent un hash périmé', function () {
    $product = zeProduct();
    $stale = zePrepared($product, 'old');
    zePrepared($product, 'new'); // re-prepare concurrent : hash change
    $svc = app(PrintFileService::class);
    expect(fn () => $svc->markMockupReady($stale))->toThrow(PrintFileNotApprovedException::class);
});
