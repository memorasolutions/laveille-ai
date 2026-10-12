<?php

/**
 * Annulation admin d'une commande : Gelato d'abord, remboursement Stripe ensuite, statut inchangé au moindre non-succès.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Models\Order;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

function ocAdmin(): User
{
    config(['app.superadmin_email' => 'fondateur@example.com', 'shop.founder_only' => false, 'shop.gelato.api_key' => 'k', 'shop.stripe.secret_key' => 'sk_test']);
    $user = User::where('email', 'fondateur@example.com')->first() ?? User::factory()->create(['email' => 'fondateur@example.com']);
    $user->assignRole(Role::findOrCreate('super_admin', 'web'));

    return $user;
}

function ocOrder(array $over = []): Order
{
    return Order::create(array_merge([
        'email' => 'c@example.com', 'status' => 'paid', 'subtotal' => 30, 'total' => 30,
        'stripe_payment_intent_id' => 'pi_123', 'shipping_address' => ['first_name' => 'A'],
    ], $over));
}

function ocCancel(Order $o)
{
    return test()->actingAs(ocAdmin())->from('/admin/shop/orders')->post("/admin/shop/orders/{$o->id}/cancel");
}

function ocSent(string $needle): bool
{
    return Http::recorded(fn ($req) => str_contains($req->url(), $needle))->isNotEmpty();
}

test('payée + jamais soumise : remboursement Stripe, statut refunded', function () {
    Http::fake(['api.stripe.com/*' => Http::response(['id' => 're_1'], 200)]);
    $o = ocOrder();

    ocCancel($o)->assertSessionHas('success');

    expect($o->fresh()->status)->toBe('refunded');
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'api.stripe.com/v1/refunds')
        && $r->hasHeader('Idempotency-Key', 'refund_order_'.$o->id)
        && $r['payment_intent'] === 'pi_123');
    expect(ocSent('gelatoapis.com'))->toBeFalse();
});

test('payée + soumise + Gelato annulable : cancel PUIS refund, statut refunded', function () {
    Http::fake([
        'order.gelatoapis.com/*' => Http::response(['id' => 'G-1'], 200),
        'api.stripe.com/*' => Http::response(['id' => 're_1'], 200),
    ]);
    $o = ocOrder(['gelato_order_id' => 'G-1', 'gelato_submit_state' => 'submitted']);

    ocCancel($o)->assertSessionHas('success');

    expect($o->fresh()->status)->toBe('refunded');
    $urls = Http::recorded()->map(fn ($p) => $p[0]->url())->values()->all();
    expect($urls[0])->toContain('order.gelatoapis.com/v4/orders/G-1:cancel');
    expect($urls[1])->toContain('api.stripe.com/v1/refunds');
});

test('payée + soumise + Gelato non annulable (422) : BLOQUÉ, aucun refund, statut inchangé', function () {
    Http::fake(['order.gelatoapis.com/*' => Http::response(['message' => 'already in production'], 422), 'api.stripe.com/*' => Http::response([], 200)]);
    $o = ocOrder(['gelato_order_id' => 'G-1', 'gelato_submit_state' => 'submitted']);

    ocCancel($o)->assertSessionHas('error');

    expect($o->fresh()->status)->toBe('paid');
    expect(ocSent('api.stripe.com'))->toBeFalse();
});

test('payée + soumise + Gelato en erreur transitoire (500) : BLOQUÉ aussi, aucun refund', function () {
    Http::fake(['order.gelatoapis.com/*' => Http::response('boom', 500), 'api.stripe.com/*' => Http::response([], 200)]);
    $o = ocOrder(['gelato_order_id' => 'G-1', 'gelato_submit_state' => 'submitted']);

    ocCancel($o)->assertSessionHas('error');

    expect($o->fresh()->status)->toBe('paid');
    expect(ocSent('api.stripe.com'))->toBeFalse();
});

test('refund Stripe refusé (400) : BLOQUÉ, statut inchangé', function () {
    Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'nope']], 400)]);
    $o = ocOrder();

    ocCancel($o)->assertSessionHas('error');

    expect($o->fresh()->status)->toBe('paid');
});

test('pending jamais payée : cancelled, aucun appel externe', function () {
    Http::fake();
    $o = ocOrder(['status' => 'pending', 'stripe_payment_intent_id' => null]);

    ocCancel($o)->assertSessionHas('success');

    expect($o->fresh()->status)->toBe('cancelled');
    Http::assertNothingSent();
});

test('refunded, fulfilled, shipped : bloqués d\'emblée, aucun appel externe', function (string $status) {
    Http::fake();
    $o = ocOrder(['status' => $status]);

    ocCancel($o)->assertSessionHas('error');

    expect($o->fresh()->status)->toBe($status);
    Http::assertNothingSent();
})->with(['refunded', 'fulfilled', 'shipped']);

test('envoi Gelato incertain (unknown/submitting) : BLOQUÉ, aucun appel externe', function (string $state) {
    Http::fake();
    $o = ocOrder(['gelato_submit_state' => $state]);

    ocCancel($o)->assertSessionHas('error');

    expect($o->fresh()->status)->toBe('paid');
    Http::assertNothingSent();
})->with(['unknown', 'submitting']);

test('double appel : un seul remboursement, le 2e est bloqué par le garde refunded', function () {
    Http::fake(['api.stripe.com/*' => Http::response(['id' => 're_1'], 200)]);
    $o = ocOrder();

    ocCancel($o)->assertSessionHas('success');
    ocCancel($o)->assertSessionHas('error');

    expect(Http::recorded(fn ($req) => str_contains($req->url(), '/v1/refunds'))->count())->toBe(1);
});

test('fail-closed : un gelato_order_id présent dans un état NON-submitted force quand même l\'annulation Gelato avant tout refund', function () {
    // Invariant actuel : order_id et "submitted" sont posés ensemble. Ce test verrouille le durcissement contre une
    // régression future qui découplerait les deux : dès qu'un id Gelato existe, on exige son annulation.
    Http::fake(['order.gelatoapis.com/*' => Http::response(['message' => 'already in production'], 422), 'api.stripe.com/*' => Http::response([], 200)]);
    $o = ocOrder(['gelato_order_id' => 'G-9', 'gelato_submit_state' => 'rejected']);

    ocCancel($o)->assertSessionHas('error');

    expect($o->fresh()->status)->toBe('paid');
    expect(ocSent('order.gelatoapis.com/v4/orders/G-9:cancel'))->toBeTrue();
    expect(ocSent('api.stripe.com'))->toBeFalse();
});

test('refund Stripe HTTP 200 mais statut failed : BLOQUÉ, commande jamais marquée remboursée', function () {
    Http::fake(['api.stripe.com/*' => Http::response(['id' => 're_x', 'status' => 'failed'], 200)]);
    $o = ocOrder();

    ocCancel($o)->assertSessionHas('error');

    expect($o->fresh()->status)->toBe('paid');
});
