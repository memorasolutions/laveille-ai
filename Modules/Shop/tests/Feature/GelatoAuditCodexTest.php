<?php

/**
 * Revue adversariale Codex (2026-10-08) : 16 défauts zone argent + confidentialité de la boutique Gelato.
 * Un test par défaut, qui REPRODUIT le symptôme (prix de base, mauvaise taille, énumération de commandes...).
 * Nommage : « #n » = numéro du défaut dans la revue.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Gelato\GelatoReconciler;
use Modules\Shop\Gelato\GelatoRoutingException;
use Modules\Shop\Gelato\OrderItemRouter;
use Modules\Shop\Listeners\CreateGelatoOrder;
use Modules\Shop\Models\Cart;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderItem;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\CartService;
use Modules\Shop\Services\GelatoService;
use Modules\Shop\Services\GelatoSyncService;

uses(Tests\TestCase::class, RefreshDatabase::class);

const AC_STORE = 'store-ac';

function acUid(string $color, string $size): string
{
    return 'apparel_product_gca_t-shirt_gsi_'.strtolower($size).'_gco_'.$color.'_gpr_4-0';
}

/** Produit catalogue synchronisé : 2 couleurs (Blanc / Rose) x tailles M, XL, 2XL (2XL plus cher). */
function acProduct(array $over = []): Product
{
    $variants = [];
    $map = [];
    foreach (['white' => 'Blanc', 'pink' => 'Rose'] as $slug => $label) {
        $v = ['color' => '#FFFFFF', 'color_slug' => $slug, 'label' => $label, 'size_prices' => [], 'variant_ids' => [], 'product_uids' => [], 'images' => [], 'gelato_uid' => acUid($slug, 'M'), 'sort_order' => 0];
        foreach (['M' => 20.99, 'XL' => 20.99, '2XL' => 29.99] as $size => $price) {
            $v['size_prices'][$size] = $price;
            $v['variant_ids'][$size] = "sv-{$slug}-{$size}";
            $v['product_uids'][$size] = acUid($slug, $size);
            $map[acUid($slug, $size)] = "sv-{$slug}-{$size}";
        }
        $variants[] = $v;
    }

    return Product::create(array_merge([
        'name' => 'T-shirt', 'slug' => 'ts-'.uniqid(), 'price' => 20.99, 'status' => 'published', 'variants' => $variants,
        'metadata' => ['gelato_store_product_id' => 'sp-1', 'store_variant_map' => $map, 'sizes' => ['M', 'XL', '2XL'], 'cost_base' => 8.0, 'cost_currency' => 'USD'],
    ], $over));
}

function acUser(): User
{
    return User::factory()->create();
}

function acCart(User $user, array $items): Cart
{
    return Cart::create(['user_id' => $user->id, 'session_id' => 's-'.uniqid(), 'items' => $items, 'expires_at' => now()->addDay()]);
}

function acBareOrder(array $attrs): Order
{
    return Order::create($attrs + ['shipping_address' => ['first_name' => 'A', 'last_name' => 'B']]);
}

function acPaidOrder(Product $product, array $itemOver = [], array $orderOver = []): Order
{
    $order = Order::create(array_merge([
        'email' => 'c@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30,
        'shipping_address' => ['first_name' => 'A', 'last_name' => 'B'],
    ], $orderOver));
    OrderItem::create(array_merge([
        'order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30,
        'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'),
    ], $itemOver));

    return $order->fresh('items.product');
}

beforeEach(function () {
    config([
        'shop.gelato.api_key' => 'k', 'shop.stripe.secret_key' => 'sk_test_factice', 'shop.gelato.store_id' => AC_STORE, 'shop.gelato_zero_erreur' => true,
        'shop.admin_email' => 'a@example.com', 'shop.handling_fee' => 0, 'shop.maintenance' => false, 'shop.founder_only' => false, 'shop.gelato_sync_autopublish' => true,
    ]);
    Mail::fake();
    Cache::put('exchange_rate_usd_cad', 1.40, 3600);
});

// =====================================================================================================================
// CONFIDENTIALITÉ (Loi 25) : /confirmation/{order}
// =====================================================================================================================

test('CONFIDENTIALITÉ : un inconnu qui devine un id de commande reçoit 404, sans aucune donnée', function () {
    $order = acBareOrder(['email' => 'victime@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 99.5, 'stripe_session_id' => 'cs_secret_1']);

    $this->get(route('shop.confirmation', $order))->assertNotFound();
    $this->get(route('shop.confirmation', $order).'?session_id=cs_autre')->assertNotFound();
    $this->get(route('shop.confirmation', $order).'?session_id=')->assertNotFound();
});

test('CONFIDENTIALITÉ : le porteur du session_id Stripe de CETTE commande la voit', function () {
    $order = acBareOrder(['email' => 'client@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30, 'stripe_session_id' => 'cs_secret_2']);

    $this->get(route('shop.confirmation', $order).'?session_id=cs_secret_2')->assertOk()->assertSee('client@example.com');
});

test('CONFIDENTIALITÉ : propriétaire connecté oui, autre utilisateur connecté non', function () {
    $owner = acUser();
    $order = acBareOrder(['user_id' => $owner->id, 'email' => 'o@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30]);

    $this->actingAs($owner)->get(route('shop.confirmation', $order))->assertOk();
    $this->actingAs(acUser())->get(route('shop.confirmation', $order))->assertNotFound();
});

test('CONFIDENTIALITÉ : le navigateur qui a créé la commande la voit (session), pas un autre', function () {
    $order = acBareOrder(['email' => 'o@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 30]);

    $this->withSession(['shop.owned_orders' => [$order->id]])->get(route('shop.confirmation', $order))->assertOk();
    $this->withSession(['shop.owned_orders' => [$order->id + 1]])->get(route('shop.confirmation', $order))->assertNotFound();
});

test('CONFIDENTIALITÉ : la page de paiement n est ouverte que pour la commande créée dans cette session', function () {
    $mine = acBareOrder(['email' => 'm@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 30]);
    $other = acBareOrder(['email' => 'x@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 30]);

    $this->withSession(['stripe_client_secret' => 'sec', 'shop.pay_order_id' => $mine->id])->get(route('shop.checkout.pay', $other))->assertNotFound();
});

// =====================================================================================================================
// PRIX / VARIANTE
// =====================================================================================================================

test('#1 le libellé n est jamais doublé : « Blanc - 2XL » + taille 2XL = tarif 2XL (29,99), pas le prix de base', function () {
    $user = acUser();
    $product = acProduct();

    $this->actingAs($user)->post(route('shop.cart.add'), [
        'product_id' => $product->id, 'quantity' => 1,
        'variant_label' => 'Blanc - 2XL', 'size_label' => '2XL', 'variant_gelato_uid' => acUid('white', 'M'),
    ])->assertSessionHasNoErrors();

    $item = Cart::where('user_id', $user->id)->firstOrFail()->items[0];
    expect($item['variant_label'])->toBe('Blanc - 2XL')
        ->and((float) $item['unit_price'])->toBe(29.99)
        ->and($item['gelato_variant_id'])->toBe(acUid('white', '2XL'));
});

test('#1 un ancien panier au libellé doublé est ramené à la variante exacte et au bon prix au revalidatePrices', function () {
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - 2XL - 2XL', 'gelato_variant_id' => acUid('white', 'M'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user);
    app(CartService::class)->revalidatePrices();

    $item = Cart::where('user_id', $user->id)->firstOrFail()->items[0];
    expect($item['variant_label'])->toBe('Blanc - 2XL')
        ->and((float) $item['unit_price'])->toBe(29.99)
        ->and($item['gelato_variant_id'])->toBe(acUid('white', '2XL'));
});

test('#1 variante inexistante : refusée, jamais de repli silencieux sur le prix de base', function () {
    $user = acUser();
    $product = acProduct();

    $this->actingAs($user)->post(route('shop.cart.add'), ['product_id' => $product->id, 'variant_label' => 'Vert - XL', 'size_label' => 'XL'])
        ->assertSessionHas('error');

    expect(Cart::where('user_id', $user->id)->exists())->toBeFalse();
});

test('non-régression : produit sans variante et produit à tailles seules (ancien format) restent vendables', function () {
    $user = acUser();
    $mug = Product::create(['name' => 'Tasse', 'slug' => 'tasse', 'price' => 18.5, 'status' => 'published']);
    $sizes = Product::create(['name' => 'Affiche', 'slug' => 'affiche', 'price' => 25, 'status' => 'published',
        'variants' => [['label' => 'A4', 'gelato_uid' => 'poster-a4'], ['label' => 'A3', 'gelato_uid' => 'poster-a3']]]);

    $this->actingAs($user)->post(route('shop.cart.add'), ['product_id' => $mug->id, 'quantity' => 2])->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('shop.cart.add'), ['product_id' => $sizes->id, 'variant_label' => 'A3', 'variant_gelato_uid' => 'poster-a3'])->assertSessionHasNoErrors();

    $items = Cart::where('user_id', $user->id)->firstOrFail()->items;
    expect($items)->toHaveCount(2)
        ->and((float) $items[0]['unit_price'])->toBe(18.5)->and($items[0]['quantity'])->toBe(2)
        ->and($items[1]['gelato_variant_id'])->toBe('poster-a3')->and((float) $items[1]['unit_price'])->toBe(25.0);
});

test('#2 changer la couleur d un article XL réaligne l identifiant sur Rose + XL, même si le navigateur envoie l uid M', function () {
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->postJson(route('shop.cart.variant'), [
        'product_id' => $product->id, 'old_variant_label' => 'Blanc - XL', 'new_variant_label' => 'Rose - XL',
        'new_gelato_uid' => acUid('pink', 'M'), // l'ancien panier envoyait l'uid de RÉFÉRENCE (taille M)
    ])->assertOk();

    $item = Cart::where('user_id', $user->id)->firstOrFail()->items[0];
    expect($item['variant_label'])->toBe('Rose - XL')->and($item['gelato_variant_id'])->toBe(acUid('pink', 'XL'));
});

test('#2 changer la taille 2XL : prix et identifiant suivent (couleur conservée)', function () {
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Rose - M', 'gelato_variant_id' => acUid('pink', 'M'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->postJson(route('shop.cart.variant'), ['product_id' => $product->id, 'old_variant_label' => 'Rose - M', 'new_variant_label' => 'Rose - 2XL'])->assertOk();

    $item = Cart::where('user_id', $user->id)->firstOrFail()->items[0];
    expect($item['gelato_variant_id'])->toBe(acUid('pink', '2XL'))->and((float) $item['unit_price'])->toBe(29.99);
});

test('#2 variante inexistante au panier : 422, article inchangé', function () {
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Rose - M', 'gelato_variant_id' => acUid('pink', 'M'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->postJson(route('shop.cart.variant'), ['product_id' => $product->id, 'old_variant_label' => 'Rose - M', 'new_variant_label' => 'Rose - 9XL'])->assertStatus(422);

    expect(Cart::where('user_id', $user->id)->firstOrFail()->items[0]['variant_label'])->toBe('Rose - M');
});

function acDetail(array $variants, string $id = 'sp-x'): array
{
    return ['id' => $id, 'title' => 'T-shirt', 'description' => '', 'status' => 'active', 'isReadyToPublish' => true,
        'previewUrl' => 'https://img.test/p.png', 'variants' => $variants, 'productVariantAttributes' => [], 'productImages' => []];
}

test('#3 productUid sans motif gco/gpr : chaque couleur garde SON identifiant (plus de seau « unknown » où l une écrase l autre)', function () {
    $detail = acDetail([
        ['id' => 'v-rose', 'title' => 'Rose - M - DTG', 'productUid' => 'uid-brut-rose-m'],
        ['id' => 'v-vert', 'title' => 'Vert - M - DTG', 'productUid' => 'uid-brut-vert-m'],
    ]);

    $variants = app(GelatoSyncService::class)->transformToLocalVariants($detail, 20.99);

    expect($variants)->toHaveCount(2);
    $byLabel = collect($variants)->keyBy('label');
    expect($byLabel['Rose']['product_uids']['M'])->toBe('uid-brut-rose-m')
        ->and($byLabel['Vert']['product_uids']['M'])->toBe('uid-brut-vert-m')
        ->and($byLabel['Rose']['variant_ids']['M'])->toBe('v-rose');
});

test('#3 deux productUid pour la même couleur et taille : la case est RETIRÉE de la vente, jamais écrasée en silence', function () {
    $detail = acDetail([
        ['id' => 'v-1', 'title' => 'Rose - M - DTG', 'productUid' => 'uid-a'],
        ['id' => 'v-2', 'title' => 'Rose - M - Brodé', 'productUid' => 'uid-b'],
        ['id' => 'v-3', 'title' => 'Rose - XL - DTG', 'productUid' => 'uid-c'],
    ]);

    $svc = app(GelatoSyncService::class);
    $variants = $svc->transformToLocalVariants($detail, 20.99);

    expect($variants)->toHaveCount(1)
        ->and(array_keys($variants[0]['product_uids']))->toBe(['XL'])
        ->and($svc->lastAmbiguities)->not->toBeEmpty();
});

// =====================================================================================================================
// MAPPING (4, 5, 6)
// =====================================================================================================================

test('#4 la resynchro ciblée ne réaffecte JAMAIS un produit Gelato déjà associé à un autre produit local', function () {
    $a = acProduct(['slug' => 'produit-a', 'metadata' => []]); // produit local pas encore rattaché : seul le garde « propriétaire » le protège
    $b = acProduct(['slug' => 'produit-b', 'metadata' => ['gelato_store_product_id' => 'sp-B', 'store_variant_map' => [acUid('white', 'XL') => 'sv-B-xl']]]);
    $b->gelato_store_product_id = 'sp-B';
    $b->save();
    Http::fake();

    $result = app(GelatoSyncService::class)->syncProductBySlug('produit-a', 'sp-B');

    expect($result['ok'])->toBeFalse();
    Http::assertNothingSent();
    expect($a->fresh()->metadata)->toBe([])->and($a->fresh()->gelato_store_product_id)->toBeNull();
});

test('#4 un produit déjà rattaché à un autre produit Gelato exige une réaffectation EXPLICITE, puis colonne + métadonnées + mapping restent cohérents', function () {
    $a = acProduct(['slug' => 'produit-a']);
    $a->gelato_store_product_id = 'sp-1';
    $a->save();
    $detail = acDetail([['id' => 'sv-new', 'title' => 'Blanc - XL - DTG', 'productUid' => acUid('white', 'XL')]], 'sp-NEW');
    Http::fake(['ecommerce.gelatoapis.com/*' => Http::response($detail), 'product.gelatoapis.com/*' => Http::response([['price' => 10.0]])]);
    $svc = app(GelatoSyncService::class);

    expect($svc->syncProductBySlug('produit-a', 'sp-NEW')['ok'])->toBeFalse();
    expect($a->fresh()->metadata['gelato_store_product_id'])->toBe('sp-1');

    expect($svc->syncProductBySlug('produit-a', 'sp-NEW', true)['ok'])->toBeTrue();
    $fresh = $a->fresh();
    expect($fresh->gelato_store_product_id)->toBe('sp-NEW')
        ->and($fresh->metadata['gelato_store_product_id'])->toBe('sp-NEW')
        ->and($fresh->metadata['store_variant_map'])->toBe([acUid('white', 'XL') => 'sv-new']);
});

test('#4 le routeur refuse un produit dont la colonne Gelato contredit le marqueur du mapping', function () {
    $p = acProduct();
    $p->gelato_store_product_id = 'sp-AUTRE';
    $p->save();
    $order = acPaidOrder($p);

    expect(fn () => app(OrderItemRouter::class)->resolvePayload($order->items->first()))->toThrow(GelatoRoutingException::class, 'incohérent');
});

test('#5 deux variantes au même productUid (designs différents) : aucune ne « survit » dans le mapping ni dans la vente', function () {
    $detail = acDetail([
        ['id' => 'design-1', 'title' => 'Blanc - XL - DTG', 'productUid' => acUid('white', 'XL')],
        ['id' => 'design-2', 'title' => 'Blanc - XL - DTG', 'productUid' => acUid('white', 'XL')],
        ['id' => 'ok-m', 'title' => 'Blanc - M - DTG', 'productUid' => acUid('white', 'M')],
    ]);
    $svc = app(GelatoSyncService::class);

    expect($svc->buildStoreVariantMap($detail))->toBe([acUid('white', 'M') => 'ok-m'])
        ->and($svc->findAmbiguousUids($detail))->toBe([acUid('white', 'XL')]);
    $variants = $svc->transformToLocalVariants($detail, 20.99);
    expect(array_keys($variants[0]['product_uids']))->toBe(['M']);
});

test('#6 produit ARCHIVÉ après paiement : l identifiant figé prime, la commande part', function () {
    Http::fake(['order.gelatoapis.com/*' => Http::response(['id' => 'G-ARCH'], 200)]);
    $p = acProduct();
    $order = acPaidOrder($p, ['gelato_store_product_variant_id' => 'sv-white-XL']);
    $p->delete(); // archivé (SoftDeletes) entre le paiement et le job

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v4/orders') && $r['items'][0]['storeProductVariantId'] === 'sv-white-XL');
    expect(Order::find($order->id)->gelato_order_id)->toBe('G-ARCH')->and(Order::find($order->id)->status)->toBe('processing');
});

test('#6 relation produit introuvable (null) : l identifiant figé suffit encore, il prime sur la relation vivante', function () {
    $item = (object) ['id' => 7, 'product' => null, 'gelato_variant_id' => acUid('white', 'XL'), 'gelato_store_product_variant_id' => 'sv-white-XL', 'quantity' => 2];

    $payload = app(OrderItemRouter::class)->resolvePayload($item);

    expect($payload)->toBe(['itemReferenceId' => '7', 'storeProductVariantId' => 'sv-white-XL', 'quantity' => 2]);
});

test('#6 produit archivé sans valeur figée : la relation vivante inclut l archivé, le mapping courant sert', function () {
    $p = acProduct();
    $order = acPaidOrder($p);
    $p->delete();

    $payload = app(OrderItemRouter::class)->resolvePayload(OrderItem::where('order_id', $order->id)->first());

    expect($payload['storeProductVariantId'])->toBe('sv-white-XL');
});

// =====================================================================================================================
// RÉCONCILIATION (7, 8, 9)
// =====================================================================================================================

function acReconcile(array $orders, ?callable $tweak = null): array
{
    // L'API v4 renvoie les `metadata` gravées à la création : par défaut, une commande trouvée porte NOTRE marqueur.
    $orders = array_map(fn ($o) => array_key_exists('metadata', $o) ? $o : $o + ['metadata' => [['key' => 'memora_env', 'value' => app(GelatoService::class)->envMarker()]]], $orders);
    Http::fake([
        'order.gelatoapis.com/v4/orders:search' => Http::response(['orders' => $orders], 200),
        'order.gelatoapis.com/v4/orders' => Http::response(['id' => 'G-NEW'], 200),
    ]);
    $o = acPaidOrder(acProduct());
    $o->forceFill(['order_number' => 'REF-AC'])->save();
    Order::whereKey($o->id)->update(['gelato_submit_key' => 'k', 'gelato_submit_state' => 'unknown']);
    if ($tweak) {
        $tweak($o);
    }

    return [app(GelatoReconciler::class)->reconcile(Order::find($o->id)), Order::find($o->id)];
}

test('#7a une commande draft/draft n est JAMAIS adoptée (ni marquée processing)', function () {
    [$result, $order] = acReconcile([['id' => 'G-DRAFT', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'draft', 'fulfillmentStatus' => 'draft']]);

    expect($result)->toBe('unknown')->and($order->gelato_order_id)->toBeNull()->and($order->status)->not->toBe('processing');
});

test('#7b une référence A valide + B au statut incomplet n adopte PAS A (ambigu)', function () {
    [$result, $order] = acReconcile([
        ['id' => 'G-A', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created'],
        ['id' => 'G-B', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid'],
    ]);

    expect($result)->toBe('unknown')->and($order->gelato_order_id)->toBeNull()->and($order->gelato_issue)->toContain('G-B');
});

test('#7c une référence réutilisée dans un AUTRE environnement du même compte Gelato n est jamais adoptée', function () {
    config(['shop.gelato_env_marker' => 'laveille.ai']);
    [$result, $order] = acReconcile([
        ['id' => 'G-STAGING', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created',
            'metadata' => [['key' => 'memora_env', 'value' => 'staging.laveille.ai']]],
    ]);

    expect($result)->toBe('unknown')->and($order->gelato_order_id)->toBeNull();
});

test('#7c la commande de NOTRE environnement est adoptée; le marqueur est gravé sur chaque commande envoyée', function () {
    config(['shop.gelato_env_marker' => 'laveille.ai']);
    [$result, $order] = acReconcile([
        ['id' => 'G-OTHER', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created', 'metadata' => [['key' => 'memora_env', 'value' => 'staging']]],
        ['id' => 'G-OURS', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'in_production', 'metadata' => [['key' => 'memora_env', 'value' => 'laveille.ai']]],
    ]);

    expect($result)->toBe('adopted')->and($order->gelato_order_id)->toBe('G-OURS');

    expect(app(GelatoService::class)->buildOrderBody(acPaidOrder(acProduct()))['metadata'])->toBe([['key' => 'memora_env', 'value' => 'laveille.ai']]);
});

test('#7 l adoption n arrache pas une commande ANNULÉE entre-temps à son statut', function () {
    [$result, $order] = acReconcile(
        [['id' => 'G-1', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created']],
        fn ($o) => Order::whereKey($o->id)->update(['status' => 'cancelled'])
    );

    expect($result)->toBe('adopted')->and($order->gelato_order_id)->toBe('G-1')->and($order->status)->toBe('cancelled');
});

test('#8 drapeau OFF : un rejet HTTP 400 est DÉFINITIF, la clé est libérée (plus d UNKNOWN bloquant)', function () {
    config(['shop.gelato_zero_erreur' => false]);
    Http::fake(['order.gelatoapis.com/v4/orders' => Http::response(['message' => 'invalid'], 400)]);
    $order = acPaidOrder(acProduct());

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));

    $fresh = Order::find($order->id);
    expect($fresh->gelato_submit_key)->toBeNull()->and($fresh->gelato_submit_state)->toBe('rejected')->and($fresh->gelato_order_id)->toBeNull();
});

test('#8 drapeau OFF : un 5xx reste UNKNOWN (clé conservée), et la commande de réconciliation tourne quand même', function () {
    config(['shop.gelato_zero_erreur' => false]);
    Http::fake([
        'order.gelatoapis.com/v4/orders:search' => Http::response(['orders' => [['id' => 'G-R', 'orderReferenceId' => 'X', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created', 'metadata' => [['key' => 'memora_env', 'value' => app(\Modules\Shop\Services\GelatoService::class)->envMarker()]]]]], 200),
        'order.gelatoapis.com/v4/orders' => Http::response('boom', 500),
    ]);
    $order = acPaidOrder(acProduct());
    $order->forceFill(['order_number' => 'X'])->save();

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));
    expect(Order::find($order->id)->gelato_submit_state)->toBe('unknown')->and(Order::find($order->id)->gelato_submit_key)->not->toBeNull();

    $this->artisan('shop:gelato-reconcile', ['--minutes' => 0])->assertSuccessful();

    expect(Order::find($order->id)->gelato_order_id)->toBe('G-R');
});

test('#9 rejeu après timeout alors que le produit a disparu : le statut n est PAS réécrit en awaiting_print_file, on réconcilie', function () {
    Http::fake([
        'order.gelatoapis.com/v4/orders:search' => Http::response(['orders' => [['id' => 'G-LIVE', 'orderReferenceId' => 'REF-9', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'in_production', 'metadata' => [['key' => 'memora_env', 'value' => app(\Modules\Shop\Services\GelatoService::class)->envMarker()]]]]], 200),
        'order.gelatoapis.com/v4/orders' => Http::response(['id' => 'G-DUP'], 200),
    ]);
    $product = acProduct();
    $order = acPaidOrder($product);
    $order->forceFill(['order_number' => 'REF-9'])->save();
    // tentative antérieure en timeout : clé posée, issue inconnue, plus de 10 minutes
    Order::whereKey($order->id)->update(['gelato_submit_key' => 'laveille-shop-order-REF-9', 'gelato_submit_state' => 'unknown', 'gelato_submit_started_at' => now()->subHour()]);
    // le mapping a disparu depuis : le corps ne se construit plus
    $product->update(['metadata' => ['gelato_store_product_id' => 'sp-1', 'store_variant_map' => []]]);

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid(Order::find($order->id)));

    $fresh = Order::find($order->id);
    expect($fresh->status)->not->toBe('awaiting_print_file')->and($fresh->gelato_order_id)->toBe('G-LIVE');
    Http::assertNotSent(fn ($r) => $r->url() === 'https://order.gelatoapis.com/v4/orders');
});

// =====================================================================================================================
// ANNULATION / DOUBLE CHECKOUT (10, 11)
// =====================================================================================================================

test('#10 annulée après paiement et AVANT le job : le job ne commande rien et ne remet pas processing', function () {
    Http::fake(['order.gelatoapis.com/*' => Http::response(['id' => 'G-X'], 200)]);
    $order = acPaidOrder(acProduct());
    Order::whereKey($order->id)->update(['status' => 'cancelled']); // annulation admin

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order)); // l'événement porte l'ancienne instance « paid »

    Http::assertNothingSent();
    $fresh = Order::find($order->id);
    expect($fresh->status)->toBe('cancelled')->and($fresh->gelato_order_id)->toBeNull()->and($fresh->gelato_submit_key)->toBeNull();
});

test('#10 annulée PENDANT l envoi : la commande Gelato créée est tracée, le statut annulé est conservé, l admin est alerté', function () {
    Http::fake(['order.gelatoapis.com/v4/orders' => function () use (&$orderId) {
        Order::whereKey($orderId)->update(['status' => 'cancelled']);

        return Http::response(['id' => 'G-RACE'], 200);
    }]);
    $order = acPaidOrder(acProduct());
    $orderId = $order->id;

    app(CreateGelatoOrder::class)->handle(new ShopOrderPaid($order));

    $fresh = Order::find($order->id);
    expect($fresh->status)->toBe('cancelled')->and($fresh->gelato_order_id)->toBe('G-RACE');
});

function acCheckoutPayload(array $over = []): array
{
    return array_merge([
        'email' => 'c@example.com', 'shipping_cost' => 12.5,
        'shipping_address' => ['first_name' => 'A', 'last_name' => 'B', 'address_line1' => '1 rue X', 'city' => 'Laval', 'state' => 'QC', 'postal_code' => 'H7N1A1', 'country' => 'CA'],
    ], $over);
}

function acFakeCheckoutApis(?array $methods = null): void
{
    $methods ??= [['shipmentMethodUid' => 'std', 'name' => 'Standard', 'price' => 12.5, 'currency' => 'CAD'], ['shipmentMethodUid' => 'express', 'name' => 'Express', 'price' => 29.0, 'currency' => 'CAD']];
    Http::fake([
        'order.gelatoapis.com/v4/orders:quote' => Http::response(['quotes' => [['shipmentMethods' => $methods]]], 200),
        'api.stripe.com/*' => Http::response(['id' => 'cs_test_1', 'client_secret' => 'sec_1'], 200),
    ]);
}

test('#11 deux requêtes simultanées sur le même panier : celle qui ne tient pas le verrou ne crée AUCUNE commande', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    $cart = acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $held = Cache::lock('shop:checkout:cart:'.$cart->id, 60); // la 1re requête est en vol
    expect($held->get())->toBeTrue();

    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload())->assertSessionHas('error');

    expect(Order::count())->toBe(0);
    $held->release();
});

test('#11 checkout normal : une commande, panier vidé; un 2e envoi n en recrée pas', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload())->assertRedirect();
    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload())->assertSessionHas('error');

    expect(Order::count())->toBe(1);
    $order = Order::first();
    expect($order->items()->first()->gelato_store_product_variant_id)->toBe('sv-white-XL');
});

// =====================================================================================================================
// DISPONIBILITÉ (14) ET PRIX À LA SYNCHRO (15)
// =====================================================================================================================

test('#14 produit dépublié (retiré de la vente) avec un ancien panier : checkout refusé, aucune commande', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct(['status' => 'draft']);
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload())->assertSessionHas('error');

    expect(Order::count())->toBe(0);
});

test('#14 variante qui n existe plus (ou sans mapping) : checkout refusé', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Vert - XL', 'gelato_variant_id' => 'x', 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload())->assertSessionHas('error');

    expect(Order::count())->toBe(0);
});

function acSyncFake(array $detail, bool $costsOk = true): void
{
    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake([
        'ecommerce.gelatoapis.com/v1/stores/*/products/'.$detail['id'] => Http::response($detail),
        'ecommerce.gelatoapis.com/v1/stores/*/products?*' => Http::response(['products' => [['id' => $detail['id']]]]),
        'product.gelatoapis.com/*' => $costsOk ? Http::response([['price' => 10.0]]) : Http::response('down', 500),
    ]);
}

function acStoreDetail(string $status = 'active'): array
{
    $d = acDetail([
        ['id' => 'v-m', 'title' => 'Blanc - M - DTG', 'productUid' => acUid('white', 'M')],
        ['id' => 'v-xl', 'title' => 'Blanc - XL - DTG', 'productUid' => acUid('white', 'XL')],
    ], 'sp-sync');
    $d['status'] = $status;

    return $d;
}

test('#14 un produit devenu inactif chez Gelato est retiré de la vente (brouillon réversible) à la synchro', function () {
    acSyncFake(acStoreDetail());
    $svc = app(GelatoSyncService::class);
    expect($svc->syncStore()['ok'])->toBeTrue();
    $p = Product::firstOrFail();
    expect($p->status)->toBe('published');

    acSyncFake(acStoreDetail('inactive'));
    $svc->syncStore();

    $p = $p->fresh();
    expect($p->status)->toBe('draft')->and($p->metadata['gelato_sync_unpublished'])->toBeTrue();

    // redevenu actif : republié
    acSyncFake(acStoreDetail());
    $svc->syncStore();
    expect($p->fresh()->status)->toBe('published');
});

test('#15 l API des coûts échoue pour un nouveau produit actif : brouillon, AUCUN prix 0 ni 20,99 arbitraire', function () {
    acSyncFake(acStoreDetail(), costsOk: false);

    $r = app(GelatoSyncService::class)->syncStore();

    expect($r['ok'])->toBeTrue();
    $p = Product::firstOrFail();
    expect($p->status)->toBe('draft')
        ->and($p->variants)->toBe([])
        ->and($p->metadata['gelato_sync_pricing_incomplete'])->toBe(2)
        ->and((float) $p->price)->toBe(0.0);
    // jamais de tarif arbitraire quelque part
    expect(json_encode($p->variants))->not->toContain('20.99');
});

test('#15 produit publié dont les coûts tombent en panne : prix publiés conservés, rien de vendu à un prix inventé', function () {
    acSyncFake(acStoreDetail());
    $svc = app(GelatoSyncService::class);
    $svc->syncStore();
    $before = Product::firstOrFail();
    $priceXl = $before->variants[0]['size_prices']['XL'];

    acSyncFake(acStoreDetail(), costsOk: false);
    $svc->syncStore(['force' => true]);

    $after = $before->fresh();
    expect($after->status)->toBe('published')->and($after->variants[0]['size_prices']['XL'])->toBe($priceXl);
});

// =====================================================================================================================
// P2 : verrou de synchro (12), liste partielle (13), livraison (16)
// =====================================================================================================================

test('#12 verrou de synchro perdu en cours de route : plus aucune écriture ni retrait', function () {
    $d1 = acDetail([['id' => 'a-m', 'title' => 'Blanc - M - DTG', 'productUid' => acUid('white', 'M')]], 'sp-one');
    $d2 = acDetail([['id' => 'b-m', 'title' => 'Rose - M - DTG', 'productUid' => acUid('pink', 'M')]], 'sp-two');
    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake([
        'ecommerce.gelatoapis.com/v1/stores/*/products/sp-one' => function () {
            Cache::lock('shop:gelato-sync')->forceRelease(); // le verrou expire pendant le traitement du 1er produit
            Cache::lock('shop:gelato-sync', 900)->get();     // et une AUTRE synchro le prend
            return Http::response(acDetail([['id' => 'a-m', 'title' => 'Blanc - M - DTG', 'productUid' => acUid('white', 'M')]], 'sp-one'));
        },
        'ecommerce.gelatoapis.com/v1/stores/*/products/sp-two' => Http::response($d2),
        'ecommerce.gelatoapis.com/v1/stores/*/products?*' => Http::response(['products' => [['id' => 'sp-one'], ['id' => 'sp-two']]]),
        'product.gelatoapis.com/*' => Http::response([['price' => 10.0]]),
    ]);
    $absent = acProduct(['slug' => 'absent']); // produit catalogue absent de la liste : serait dépublié
    $absent->gelato_store_product_id = 'sp-absent';
    $absent->save();

    $r = app(GelatoSyncService::class)->syncStore();

    expect($r['ok'])->toBeFalse()->and($r['error'])->toContain('Verrou');
    expect(Product::where('gelato_store_product_id', 'sp-two')->exists())->toBeFalse()
        ->and($absent->fresh()->status)->toBe('published');
    Cache::lock('shop:gelato-sync')->forceRelease();
});

test('#13 réponse de liste sans « products » : ne RIEN dépublier', function () {
    $p = acProduct(['slug' => 'garde']);
    $p->gelato_store_product_id = 'sp-garde';
    $p->save();
    Http::fake(['ecommerce.gelatoapis.com/v1/stores/*/products?*' => Http::response(['message' => 'oups'], 200)]);

    $r = app(GelatoSyncService::class)->syncStore();

    expect($r['ok'])->toBeFalse()->and($p->fresh()->status)->toBe('published');
});

test('#13 liste tronquée par le plafond de pages : ne RIEN dépublier', function () {
    $p = acProduct(['slug' => 'garde']);
    $p->gelato_store_product_id = 'sp-garde';
    $p->save();
    Http::fake(['ecommerce.gelatoapis.com/v1/stores/*/products?*' => function ($request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
        $offset = (int) ($q['offset'] ?? 0);

        return Http::response(['products' => array_map(fn ($i) => ['id' => 'gp-'.($offset + $i)], range(0, 99))]);
    }, 'ecommerce.gelatoapis.com/v1/stores/*/products/*' => Http::response(null, 500)]);

    $r = app(GelatoSyncService::class)->syncStore();

    expect($r['ok'])->toBeFalse()->and($r['error'])->toContain('illisible')->and($p->fresh()->status)->toBe('published');
});

test('#16 le mode de livraison CHOISI (express) est enregistré et transmis à Gelato', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload(['shipping_cost' => 29.0, 'shipping_method_uid' => 'express']))->assertRedirect();

    $order = Order::firstOrFail();
    expect($order->shipping_method_uid)->toBe('express')->and((float) $order->shipping_cost)->toBe(29.0);

    $body = app(GelatoService::class)->buildOrderBody($order->fresh('items.product'));
    expect($body['shipmentMethodUid'])->toBe('express');
});

test('#16 un uid de livraison qui ne correspond pas au prix payé est refusé', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);

    // prix du standard (12,50) mais uid « express » : incohérent
    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload(['shipping_cost' => 12.5, 'shipping_method_uid' => 'express']))->assertSessionHasErrors('shipping_cost');

    expect(Order::count())->toBe(0);
});

// =====================================================================================================================
// ROUND 2 (revue adversariale Codex du 2026-10-08, 2e passe) : 9 corrections partielles + bloquants restants
// Nommage : « R2-n » = numéro du défaut de la 2e revue.
// =====================================================================================================================

class AcMutatingCartService extends CartService
{
    /** Simule un autre onglet qui change la quantité APRÈS la capture du panier par le checkout. */
    public function getContent(): array
    {
        $content = parent::getContent();
        $cart = $this->getCart();
        $items = $cart->items;
        $items[0]['quantity'] = 7;
        $cart->update(['items' => $items]);

        return $content;
    }
}

test('R2-1 le numéro de commande des NOUVELLES commandes est aléatoire et non devinable (plus d horodatage + 3 chiffres)', function () {
    $a = acBareOrder(['email' => 'a@example.com', 'status' => 'paid', 'subtotal' => 1, 'total' => 1]);
    $b = acBareOrder(['email' => 'a@example.com', 'status' => 'paid', 'subtotal' => 1, 'total' => 1]);

    expect($a->order_number)->toMatch('/^\d{8}-[A-HJ-NP-Z2-9]{10}$/')->and($b->order_number)->not->toBe($a->order_number);
});

test('R2-1 /suivi : l identifiant séquentiel ne suffit plus; le numéro de commande + le courriel, oui', function () {
    $order = acBareOrder(['email' => 'victime@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30]);

    $this->post(route('shop.order-lookup.search'), ['order_number' => (string) $order->id, 'email' => 'victime@example.com'])
        ->assertSessionHas('error');
    $this->post(route('shop.order-lookup.search'), ['order_number' => $order->order_number, 'email' => 'victime@example.com'])
        ->assertOk()->assertSee($order->order_number);
});

test('R2-1 /suivi : réponse IDENTIQUE que la commande existe ou que le courriel soit faux (aucun oracle)', function () {
    $order = acBareOrder(['email' => 'victime@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30]);

    $wrongEmail = $this->post(route('shop.order-lookup.search'), ['order_number' => $order->order_number, 'email' => 'autre@example.com']);
    $noOrder = $this->post(route('shop.order-lookup.search'), ['order_number' => '20990101-ZZZZZZZZZZ', 'email' => 'autre@example.com']);

    expect(session('error'))->toBe('Aucune commande trouvée avec ces informations.');
    $wrongEmail->assertSessionHas('error', 'Aucune commande trouvée avec ces informations.');
    $noOrder->assertSessionHas('error', 'Aucune commande trouvée avec ces informations.');
    expect($wrongEmail->getStatusCode())->toBe($noOrder->getStatusCode());
});

test('R2-1 /suivi : anti-énumération par adresse IP ET par courriel visé (attaque répartie)', function () {
    $order = acBareOrder(['email' => 'victime@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30]);

    // 10 essais ratés depuis la même IP : le 11e est bloqué même avec les bons renseignements
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('shop.order-lookup.search'), ['order_number' => 'X'.$i, 'email' => 'victime'.$i.'@example.com']);
    }
    $this->post(route('shop.order-lookup.search'), ['order_number' => $order->order_number, 'email' => 'victime@example.com'])
        ->assertSessionHas('error', 'Trop de tentatives. Veuillez réessayer dans quelques minutes.');

    // 10 essais ratés visant le MÊME courriel, depuis 10 IP différentes : l'IP 11 est aussi bloquée
    \Illuminate\Support\Facades\RateLimiter::clear('order-lookup:ip:127.0.0.1');
    for ($i = 0; $i < 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.($i + 1)])
            ->post(route('shop.order-lookup.search'), ['order_number' => 'Y'.$i, 'email' => 'cible@example.com']);
    }
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.9.9'])
        ->post(route('shop.order-lookup.search'), ['order_number' => 'Y', 'email' => 'cible@example.com'])
        ->assertSessionHas('error', 'Trop de tentatives. Veuillez réessayer dans quelques minutes.');
});

test('R2-2 produit catalogue SANS variante synchronisée : refusé, jamais vendu au prix de base', function () {
    $user = acUser();
    $product = acProduct(['variants' => []]);

    $this->actingAs($user)->post(route('shop.cart.add'), ['product_id' => $product->id, 'quantity' => 1, 'variant_gelato_uid' => acUid('white', 'M')])
        ->assertSessionHas('error');

    expect(Cart::where('user_id', $user->id)->exists())->toBeFalse();
});

test('R2-2 variante à tailles seules SANS identifiant serveur : l identifiant du navigateur n est jamais utilisé', function () {
    $user = acUser();
    $sizes = Product::create(['name' => 'Affiche', 'slug' => 'affiche-r2', 'price' => 25, 'status' => 'published',
        'variants' => [['label' => 'A4', 'gelato_uid' => ''], ['label' => 'A3', 'gelato_uid' => 'poster-a3']]]);

    $this->actingAs($user)->post(route('shop.cart.add'), ['product_id' => $sizes->id, 'variant_label' => 'A4', 'variant_gelato_uid' => 'poster-a3'])
        ->assertSessionHas('error');
    expect(Cart::where('user_id', $user->id)->exists())->toBeFalse();

    // et pour la taille qui a SON identifiant serveur, celui du navigateur (autre taille) est ignoré
    $this->actingAs($user)->post(route('shop.cart.add'), ['product_id' => $sizes->id, 'variant_label' => 'A3', 'variant_gelato_uid' => 'poster-a4-pirate'])
        ->assertSessionHasNoErrors();
    expect(Cart::where('user_id', $user->id)->firstOrFail()->items[0]['gelato_variant_id'])->toBe('poster-a3');
});

test('R2-2 produit catalogue à tailles seules dont l identifiant n est pas dans le mapping store : refusé', function () {
    $user = acUser();
    $p = Product::create(['name' => 'Affiche', 'slug' => 'affiche-cat', 'price' => 25, 'status' => 'published',
        'variants' => [['label' => 'A3', 'gelato_uid' => 'poster-a3']],
        'metadata' => ['gelato_store_product_id' => 'sp-9', 'store_variant_map' => []]]);

    $this->actingAs($user)->post(route('shop.cart.add'), ['product_id' => $p->id, 'variant_label' => 'A3'])->assertSessionHas('error');
});

test('R2-3 changement de variante ACCEPTÉ : la réponse porte libellé canonique, prix, identifiant et totaux (source de vérité serveur)', function () {
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - M', 'gelato_variant_id' => acUid('white', 'M'), 'quantity' => 2, 'unit_price' => 20.99]]);

    $res = $this->actingAs($user)->postJson(route('shop.cart.variant'), ['product_id' => $product->id, 'old_variant_label' => 'Blanc - M', 'new_variant_label' => 'Rose - 2XL'])
        ->assertOk()
        ->assertJson(['success' => true, 'new_variant_label' => 'Rose - 2XL', 'unit_price' => 29.99, 'gelato_variant_id' => acUid('pink', '2XL'), 'subtotal' => 59.98]);
    expect($res->json('total'))->toBeGreaterThan(59.98);
});

test('R2-3 article introuvable au changement de variante (onglet périmé) : 422, jamais un faux succès', function () {
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Rose - M', 'gelato_variant_id' => acUid('pink', 'M'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->postJson(route('shop.cart.variant'), ['product_id' => $product->id, 'old_variant_label' => 'Blanc - XL', 'new_variant_label' => 'Blanc - M'])
        ->assertStatus(422)->assertJson(['success' => false]);
});

test('R2-3 la page panier traite un échec (422) comme un échec visible et recompose le libellé depuis l état courant', function () {
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - M', 'gelato_variant_id' => acUid('white', 'M'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $html = $this->actingAs($user)->get(route('shop.cart'))->assertOk()->getContent();

    expect($html)->toContain('spCartPost')            // helper qui exige r.ok ET success
        ->and($html)->toContain('cart-error')         // toast du thème
        ->and($html)->toContain("pickColor('Rose')")  // plus de libellé figé à l'affichage initial
        ->and($html)->toContain("pickSize('XL')")
        ->and($html)->toContain('item-variant-updated')
        ->and($html)->not->toContain('alert(')
        ->and($html)->not->toContain("pick('Rose - M'");
});

test('R2-4 isolation d environnement FAIL-CLOSED : une commande trouvée SANS métadonnées n est jamais adoptée', function () {
    [$result, $order] = acReconcile([['id' => 'G-NOMETA', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created', 'metadata' => []]]);

    expect($result)->toBe('unknown')->and($order->gelato_order_id)->toBeNull()->and($order->gelato_issue)->toContain('marqueur');
    Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v4/orders'));
});

test('R2-4 le client de référence ne sert plus de repli : sans marqueur, même client = pas d adoption', function () {
    [$result, $order] = acReconcile(
        [['id' => 'G-SAMEUSER', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created', 'metadata' => [], 'customerReferenceId' => 'c@example.com']]
    );

    expect($result)->toBe('unknown')->and($order->gelato_order_id)->toBeNull();
});

test('R2-4 une tentative MORTE sans métadonnées n empêche pas d adopter la valide de NOTRE environnement', function () {
    [$result, $order] = acReconcile([
        ['id' => 'G-DEAD', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'refused', 'fulfillmentStatus' => 'failed', 'metadata' => []],
        ['id' => 'G-OK', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'created'],
    ]);

    expect($result)->toBe('adopted')->and($order->gelato_order_id)->toBe('G-OK');
});

test('R2-5 pending_approval n est JAMAIS adopté ni passé en processing : attente avec alerte', function () {
    [$result, $order] = acReconcile([['id' => 'G-WAIT', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'paid', 'fulfillmentStatus' => 'pending_approval']]);

    expect($result)->toBe('unknown')->and($order->gelato_order_id)->toBeNull()->and($order->status)->not->toBe('processing')
        ->and($order->gelato_issue)->toContain('pending_approval');
    Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v4/orders'));
});

test('R2-5 invoiced/passed reste INCONNU (échec sûr, revue manuelle) : aucune adoption erronée', function () {
    [$result, $order] = acReconcile([['id' => 'G-INV', 'orderReferenceId' => 'REF-AC', 'financialStatus' => 'invoiced', 'fulfillmentStatus' => 'passed']]);

    expect($result)->toBe('unknown')->and($order->gelato_order_id)->toBeNull();
});

test('R2-6 le verrou expire PENDANT le calcul des coûts : aucune écriture (revérification juste avant d écrire)', function () {
    $detail = acDetail([['id' => 'a-m', 'title' => 'Blanc - M - DTG', 'productUid' => acUid('white', 'M')]], 'sp-slow');
    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake([
        'ecommerce.gelatoapis.com/v1/stores/*/products/sp-slow' => Http::response($detail),
        'ecommerce.gelatoapis.com/v1/stores/*/products?*' => Http::response(['products' => [['id' => 'sp-slow']]]),
        'product.gelatoapis.com/*' => function () {
            Cache::lock('shop:gelato-sync')->forceRelease(); // expiré pendant la requête de coûts...
            Cache::lock('shop:gelato-sync', 900)->get();     // ...et une autre synchro l'a repris
            return Http::response([['price' => 10.0]]);
        },
    ]);

    $r = app(GelatoSyncService::class)->syncStore();

    expect($r['ok'])->toBeFalse()->and($r['error'])->toContain('Verrou');
    expect(Product::count())->toBe(0);
    Cache::lock('shop:gelato-sync')->forceRelease();
});

test('R2-6 la resynchro ciblée passe par le MÊME verrou : refusée tant qu une synchro tient le verrou, aucune écriture', function () {
    $a = acProduct(['slug' => 'produit-lock']);
    $before = $a->fresh()->variants;
    $held = Cache::lock('shop:gelato-sync', 900);
    expect($held->get())->toBeTrue();
    Http::fake();

    $r = app(GelatoSyncService::class)->syncProductBySlug('produit-lock', 'sp-1');

    expect($r['ok'])->toBeFalse()->and($r['error'])->toContain('déjà en cours');
    Http::assertNothingSent();
    expect($a->fresh()->variants)->toBe($before);
    $held->release();
    // et le verrou est bien relâché après une resynchro réussie
    acSyncFake(acStoreDetail());
    expect(app(GelatoSyncService::class)->syncProductBySlug('produit-lock', 'sp-sync', true)['ok'])->toBeTrue();
    expect(Cache::lock('shop:gelato-sync', 5)->get())->toBeTrue();
});

test('R2-7 le panier change PENDANT le checkout : commande, sous-total et articles viennent de l instantané, pas de la relecture', function () {
    acFakeCheckoutApis();
    $this->app->singleton(CartService::class, fn () => new AcMutatingCartService());
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);

    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload())->assertRedirect();

    $order = Order::firstOrFail();
    expect((float) $order->subtotal)->toBe(20.99)
        ->and($order->items()->first()->quantity)->toBe(1)
        ->and(round((float) $order->total, 2))->toBe(round(20.99 + (float) $order->tax_amount + (float) $order->shipping_cost, 2));
    // Stripe a reçu les MÊMES articles que la commande (quantité 1, pas 7)
    Http::assertSent(fn ($r) => str_contains($r->url(), 'api.stripe.com') && ! str_contains($r->body(), '146') && ! str_contains(urldecode($r->body()), '[quantity]=7'));
});

test('R2-mineur le secret de paiement survit à un rafraîchissement et un 2e onglet n écrase pas le premier', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    $line = [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]];
    acCart($user, $line);
    $this->actingAs($user)->post(route('shop.checkout'), acCheckoutPayload())->assertRedirect();
    $first = Order::firstOrFail();

    $this->get(route('shop.checkout.pay', $first))->assertOk();
    $this->get(route('shop.checkout.pay', $first))->assertOk(); // rafraîchissement

    acCart($user, $line);                                         // 2e onglet : une autre commande
    $this->post(route('shop.checkout'), acCheckoutPayload())->assertRedirect();
    $second = Order::where('id', '!=', $first->id)->firstOrFail();

    $this->get(route('shop.checkout.pay', $first))->assertOk();   // la première reste payable
    $this->get(route('shop.checkout.pay', $second))->assertOk();
});

test('R2-8 resynchro ciblée : un produit devenu INACTIF chez Gelato est retiré de la vente (même chemin que la synchro complète)', function () {
    $p = acProduct(['slug' => 'cible', 'metadata' => []]);
    acSyncFake(acStoreDetail('inactive'));

    $r = app(GelatoSyncService::class)->syncProductBySlug('cible', 'sp-sync');

    expect($r['ok'])->toBeTrue();
    expect($p->fresh()->status)->toBe('draft')->and($p->fresh()->metadata['gelato_sync_unpublished'])->toBeTrue();
});

test('R2-8 resynchro ciblée : les prix viennent du COÛT Gelato + marge (comme la synchro complète), pas du prix local + suppléments fixes', function () {
    $p = acProduct(['slug' => 'cible2', 'metadata' => []]);
    acSyncFake(acStoreDetail());

    expect(app(GelatoSyncService::class)->syncProductBySlug('cible2', 'sp-sync')['ok'])->toBeTrue();

    $expected = Product::smartPrice(10.0, 't-shirts');
    $variants = $p->fresh()->variants;
    expect($expected)->not->toBe(20.99)
        ->and($variants[0]['size_prices']['XL'])->toBe($expected)
        ->and($variants[0]['size_prices']['M'])->toBe($expected);
});

test('R2-9 liste Gelato : une page PLEINE répétée n est pas une fin de liste, aucun produit dépublié', function () {
    $keep = acProduct(['slug' => 'garde-r2']);
    $keep->gelato_store_product_id = 'sp-hors-liste';
    $keep->save();
    Http::fake(['ecommerce.gelatoapis.com/v1/stores/*/products?*' => Http::response(['products' => array_map(fn ($i) => ['id' => 'gp-'.$i], range(0, 99))])]);
    $svc = app(GelatoSyncService::class);

    expect($svc->listStoreProducts('store-ac'))->toBeNull();
    $r = $svc->syncStore();
    expect($r['ok'])->toBeFalse()->and($keep->fresh()->status)->toBe('published');
});

test('R2-9 liste Gelato : une vraie dernière page courte reste une fin normale', function () {
    Http::fake(['ecommerce.gelatoapis.com/v1/stores/*/products?*' => function ($request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
        $offset = (int) ($q['offset'] ?? 0);

        return Http::response(['products' => $offset === 0 ? array_map(fn ($i) => ['id' => 'gp-'.$i], range(0, 99)) : [['id' => 'gp-100'], ['id' => 'gp-101']]]);
    }]);

    expect(app(GelatoSyncService::class)->listStoreProducts('store-ac'))->toHaveCount(102);
});

test('R2-10 le coût est calculé par (couleur, taille) : une couleur plus chère n est pas vendue au coût d une autre', function () {
    $detail = acDetail([
        ['id' => 'v-w', 'title' => 'Blanc - M - DTG', 'productUid' => acUid('white', 'M')],
        ['id' => 'v-p', 'title' => 'Rose - M - DTG', 'productUid' => acUid('pink', 'M')],
    ], 'sp-colors');
    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake([
        'ecommerce.gelatoapis.com/v1/stores/*/products/sp-colors' => Http::response($detail),
        'ecommerce.gelatoapis.com/v1/stores/*/products?*' => Http::response(['products' => [['id' => 'sp-colors']]]),
        'product.gelatoapis.com/v3/products/*_gco_pink_*' => Http::response([['price' => 15.0]]),
        'product.gelatoapis.com/v3/products/*_gco_white_*' => Http::response([['price' => 10.0]]),
    ]);

    expect(app(GelatoSyncService::class)->syncStore()['ok'])->toBeTrue();

    $variants = collect(Product::firstOrFail()->variants)->keyBy('color_slug');
    expect($variants['white']['size_prices']['M'])->toBe(Product::smartPrice(10.0, 't-shirts'))
        ->and($variants['pink']['size_prices']['M'])->toBe(Product::smartPrice(15.0, 't-shirts'))
        ->and($variants['pink']['size_prices']['M'])->toBeGreaterThan($variants['white']['size_prices']['M']);
});

test('R2-11 panier réparti entre DEUX centres : les expéditions s ADDITIONNENT (jamais le transport d un seul centre)', function () {
    config(['shop.handling_fee' => 0]);
    Http::fake(['order.gelatoapis.com/v4/orders:quote' => Http::response(['quotes' => [
        ['shipmentMethods' => [['shipmentMethodUid' => 'std', 'name' => 'Standard', 'price' => 5.0, 'currency' => 'CAD', 'minDeliveryDays' => 3, 'maxDeliveryDays' => 5], ['shipmentMethodUid' => 'express', 'name' => 'Express', 'price' => 20.0, 'currency' => 'CAD']]],
        ['shipmentMethods' => [['shipmentMethodUid' => 'std', 'name' => 'Standard', 'price' => 7.0, 'currency' => 'CAD', 'minDeliveryDays' => 4, 'maxDeliveryDays' => 9]]],
    ]], 200)]);

    $q = app(GelatoService::class)->getQuoteFromCart([['product_id' => 1, 'gelato_variant_id' => 'u1', 'quantity' => 1], ['product_id' => 2, 'gelato_variant_id' => 'u2', 'quantity' => 1]], ['country' => 'CA', 'postal_code' => 'H7N1A1'], 'c@example.com');

    // « std » existe dans les deux centres : 5 + 7. « express » n'existe que dans un centre : non offert.
    expect($q['methods'])->toHaveCount(1)
        ->and($q['methods'][0]['uid'])->toBe('std')->and($q['methods'][0]['price'])->toBe(12.0)
        ->and($q['methods'][0]['max_days'])->toBe(9);
});

test('R2-11 aucune méthode commune à tous les centres : devis refusé (null), jamais sous-facturé', function () {
    Http::fake(['order.gelatoapis.com/v4/orders:quote' => Http::response(['quotes' => [
        ['shipmentMethods' => [['shipmentMethodUid' => 'a', 'price' => 5.0]]],
        ['shipmentMethods' => [['shipmentMethodUid' => 'b', 'price' => 7.0]]],
    ]], 200)]);

    expect(app(GelatoService::class)->getQuoteFromCart([['product_id' => 1, 'gelato_variant_id' => 'u1', 'quantity' => 1]], ['country' => 'CA'], 'c@example.com'))->toBeNull();
});

test('R2-11 un seul centre : comportement inchangé (frais de manutention ajoutés une fois)', function () {
    config(['shop.handling_fee' => 1.0]);
    Http::fake(['order.gelatoapis.com/v4/orders:quote' => Http::response(['quotes' => [['shipmentMethods' => [['shipmentMethodUid' => 'std', 'name' => 'S', 'price' => 5.0]]]]], 200)]);

    $q = app(GelatoService::class)->getQuoteFromCart([['product_id' => 1, 'gelato_variant_id' => 'u1', 'quantity' => 1]], ['country' => 'CA'], 'c@example.com');

    expect($q['cheapest_price'])->toBe(6.0);
});

test('R2-mineur « Bleu-gris - XL » est découpé en couleur « Bleu-gris » + taille XL, et XS est conservé', function () {
    $detail = acDetail([
        ['id' => 'v-1', 'title' => 'Bleu-gris - XS - DTG', 'productUid' => 'uid-bg-xs'],
        ['id' => 'v-2', 'title' => 'Bleu-gris - XL - DTG', 'productUid' => 'uid-bg-xl'],
    ]);

    $variants = app(GelatoSyncService::class)->transformToLocalVariants($detail, 20.99);

    expect($variants)->toHaveCount(1)
        ->and($variants[0]['label'])->toBe('Bleu-gris')
        ->and(array_keys($variants[0]['size_prices']))->toBe(['XS', 'XL']);
});


// =====================================================================================================================
// RE-GATE FABLE (2026-10-08) : A1, A2, M4, M5, M8, M9, M10
// =====================================================================================================================

function acStripeCall($test, string $type, array $object)
{
    config(['shop.stripe.webhook_secret' => 'whsec']);
    $body = json_encode(['type' => $type, 'data' => ['object' => $object]]);
    $t = time();
    $sig = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$body, 'whsec');

    return $test->call('POST', route('shop.webhook.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body);
}

test('A1 code postal H7N + state=AB déclaré : la TPS+TVQ (14,975 %) est facturée, pas 5 %', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);
    $payload = acCheckoutPayload();
    $payload['shipping_address']['state'] = 'AB';

    $this->actingAs($user)->post(route('shop.checkout'), $payload)->assertRedirect();

    $order = Order::first();
    expect((float) $order->tax_amount)->toBe(round((20.99 + 12.5) * 14.975 / 100, 2))
        ->and($order->shipping_address['state'])->toBe('QC');
});

test('A1 code postal T (AB) + state=QC déclaré : seulement la TPS (la province du code postal fait foi)', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);
    $payload = acCheckoutPayload();
    $payload['shipping_address'] = array_merge($payload['shipping_address'], ['state' => 'QC', 'postal_code' => 'T2P 1J9']);

    $this->actingAs($user)->post(route('shop.checkout'), $payload)->assertRedirect();

    expect((float) Order::first()->tax_amount)->toBe(round((20.99 + 12.5) * 5 / 100, 2));
});

test('A1 première lettre de code postal canadien invalide : refusé, aucune commande', function () {
    acFakeCheckoutApis();
    $user = acUser();
    $product = acProduct();
    acCart($user, [['product_id' => $product->id, 'variant_label' => 'Blanc - XL', 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1, 'unit_price' => 20.99]]);
    $payload = acCheckoutPayload();
    $payload['shipping_address']['postal_code'] = 'Z9Z9Z9';

    $this->actingAs($user)->post(route('shop.checkout'), $payload)->assertSessionHasErrors('shipping_address.postal_code');
    expect(Order::count())->toBe(0);
});

test('A1 table des provinces : première lettre du code postal', function () {
    foreach (['A' => 'NL', 'B' => 'NS', 'C' => 'PE', 'E' => 'NB', 'G' => 'QC', 'H' => 'QC', 'J' => 'QC', 'K' => 'ON', 'L' => 'ON', 'M' => 'ON', 'N' => 'ON', 'P' => 'ON', 'R' => 'MB', 'S' => 'SK', 'T' => 'AB', 'V' => 'BC', 'X' => 'NT', 'Y' => 'YT'] as $letter => $prov) {
        expect(\Modules\Shop\Services\CanadianProvince::fromPostalCode(strtolower($letter).'1a 1a1'))->toBe($prov);
    }
    expect(\Modules\Shop\Services\CanadianProvince::fromPostalCode('D1A1A1'))->toBeNull();
});

test('A2 session completed avec payment_status=unpaid : commande NON payée, Gelato NON déclenché', function () {
    Event::fake([ShopOrderPaid::class]);
    $order = acBareOrder(['email' => 'c@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 30, 'stripe_session_id' => 'cs_unpaid']);

    acStripeCall($this, 'checkout.session.completed', ['id' => 'cs_unpaid', 'payment_status' => 'unpaid', 'amount_total' => 3000])->assertOk();

    expect(Order::find($order->id)->status)->toBe('pending');
    Event::assertNotDispatched(ShopOrderPaid::class);
});

test('A2 montant encaissé différent du total : NON marquée payée', function () {
    Event::fake([ShopOrderPaid::class]);
    $order = acBareOrder(['email' => 'c@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 30, 'stripe_session_id' => 'cs_amt']);

    acStripeCall($this, 'checkout.session.completed', ['id' => 'cs_amt', 'payment_status' => 'paid', 'amount_total' => 100])->assertOk();

    expect(Order::find($order->id)->status)->toBe('pending')->and(Order::find($order->id)->notes)->toContain('ALERTE');
    Event::assertNotDispatched(ShopOrderPaid::class);
});

test('A2 paiement différé : unpaid puis async_payment_succeeded = payée une seule fois; async_payment_failed ne paie pas', function () {
    Event::fake([ShopOrderPaid::class]);
    $order = acBareOrder(['email' => 'c@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 30, 'stripe_session_id' => 'cs_async']);

    acStripeCall($this, 'checkout.session.completed', ['id' => 'cs_async', 'payment_status' => 'unpaid', 'amount_total' => 3000]);
    expect(Order::find($order->id)->status)->toBe('pending');

    acStripeCall($this, 'checkout.session.async_payment_succeeded', ['id' => 'cs_async', 'payment_status' => 'paid', 'amount_total' => 3000, 'payment_intent' => 'pi_a']);
    expect(Order::find($order->id)->status)->toBe('paid');
    Event::assertDispatchedTimes(ShopOrderPaid::class, 1);

    $other = acBareOrder(['email' => 'd@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 30, 'stripe_session_id' => 'cs_fail']);
    acStripeCall($this, 'checkout.session.async_payment_failed', ['id' => 'cs_fail', 'payment_status' => 'unpaid', 'amount_total' => 3000])->assertOk();
    expect(Order::find($other->id)->status)->toBe('pending')->and(Order::find($other->id)->notes)->toContain('échoué');
});

test('A2 session payée et montant exact : marquée payée et événement émis', function () {
    Event::fake([ShopOrderPaid::class]);
    $order = acBareOrder(['email' => 'c@example.com', 'status' => 'pending', 'subtotal' => 30, 'total' => 34.49, 'stripe_session_id' => 'cs_ok']);

    acStripeCall($this, 'checkout.session.completed', ['id' => 'cs_ok', 'payment_status' => 'paid', 'amount_total' => 3449, 'payment_intent' => 'pi_ok'])->assertOk();

    expect(Order::find($order->id)->status)->toBe('paid');
    Event::assertDispatched(ShopOrderPaid::class);
});

test('M8 le courriel de confirmation n expose JAMAIS le courriel du client dans l URL de suivi', function () {
    $order = acBareOrder(['email' => 'secret@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30]);
    $mail = (new \Modules\Shop\Notifications\OrderConfirmedNotification($order))->toMail($order);

    expect($mail->viewData['trackingUrl'])->toContain('order_number=')->not->toContain('secret')->not->toContain('email=');
});

test('M9 un succès à /suivi ne remet PAS à zéro le compteur par IP', function () {
    $order = acBareOrder(['email' => 'moi@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30]);
    \Illuminate\Support\Facades\RateLimiter::clear('order-lookup:ip:127.0.0.1');

    for ($i = 0; $i < 9; $i++) {
        $this->post(route('shop.order-lookup.search'), ['order_number' => 'X'.$i, 'email' => 'victime'.$i.'@example.com']);
    }
    $this->post(route('shop.order-lookup.search'), ['order_number' => $order->order_number, 'email' => 'moi@example.com'])->assertOk(); // succès
    $this->post(route('shop.order-lookup.search'), ['order_number' => 'X9', 'email' => 'victime9@example.com']); // 10e coup sur l'IP

    $this->post(route('shop.order-lookup.search'), ['order_number' => $order->order_number, 'email' => 'moi@example.com'])
        ->assertSessionHas('error', 'Trop de tentatives. Veuillez réessayer dans quelques minutes.');
});

test('M10 tracking_url javascript:/data: rejetée au stockage; https conservée', function () {
    config(['shop.gelato_webhook_secret' => 's3cret']);
    $order = acBareOrder(['email' => 'c@example.com', 'status' => 'processing', 'subtotal' => 30, 'total' => 30]);
    $send = fn (string $url) => $this->postJson(route('shop.webhook.gelato'), [
        'event' => 'order_status_updated', 'orderReferenceId' => $order->order_number, 'fulfillmentStatus' => 'shipped',
        'items' => [['tracking' => ['trackingCode' => 'ABC', 'trackingUrl' => $url]]],
    ], ['X-Gelato-Secret' => 's3cret'])->assertOk();

    $send('javascript:alert(1)');
    expect(Order::find($order->id)->tracking_url)->toBeNull();
    $send('data:text/html,<script>alert(1)</script>');
    expect(Order::find($order->id)->tracking_url)->toBeNull();
    $send('https://suivi.example.com/t/ABC');
    expect(Order::find($order->id)->tracking_url)->toBe('https://suivi.example.com/t/ABC');
});

test('M5 devis : deux variantes du même produit => deux itemReferenceId distincts', function () {
    Http::fake(['order.gelatoapis.com/v4/orders:quote' => Http::response(['quotes' => [['shipmentMethods' => [['shipmentMethodUid' => 'std', 'name' => 'S', 'price' => 12.5, 'currency' => 'CAD']]]]], 200)]);
    $product = acProduct();
    $items = [
        ['product_id' => $product->id, 'gelato_variant_id' => acUid('white', 'XL'), 'quantity' => 1],
        ['product_id' => $product->id, 'gelato_variant_id' => acUid('pink', 'XL'), 'quantity' => 1],
    ];

    app(GelatoService::class)->getQuoteFromCart($items, ['country' => 'CA', 'postal_code' => 'H7N1A1', 'first_name' => 'A', 'last_name' => 'B', 'address_line1' => '1', 'city' => 'L', 'state' => 'QC'], 'c@example.com');

    $refs = [];
    Http::assertSent(function ($request) use (&$refs) {
        if (str_contains($request->url(), ':quote')) {
            $refs = array_column($request['products'] ?? [], 'itemReferenceId');
        }

        return true;
    });
    expect($refs)->toHaveCount(2)->and(array_unique($refs))->toHaveCount(2);
});

test('M4 lignes de taxe affichées : somme = taxe facturée au cent près, pour tous les sous-totaux', function () {
    for ($cents = 100; $cents <= 20000; $cents += 37) {
        $sub = $cents / 100;
        $tax = app(CartService::class)->taxAmountOf($sub);
        $order = new Order(['subtotal' => $sub, 'tax_amount' => $tax]);
        $lines = $order->taxLines();
        expect(round($lines['tps'] + $lines['tvq'], 2))->toBe(round($tax, 2));
    }
});

test('M4 hors Québec : la ligne TVQ n est pas affichée (taxe = TPS seule)', function () {
    $order = new Order(['subtotal' => 20.99, 'tax_amount' => 1.05]);
    expect($order->taxLines()['tvq'])->toBe(0.0);
});
