<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);
    $this->user = User::factory()->create();
    $this->plan = Plan::factory()->create(['stripe_price_id' => 'price_test123']);
});

// --- Authentification requise ---

test('guest ne peut pas accéder au checkout', function () {
    $this->post('/checkout')->assertRedirect();
});

test('guest ne peut pas accéder à la page de succès', function () {
    $this->get('/checkout/success')->assertRedirect();
});

test('guest ne peut pas accéder à la page d annulation', function () {
    $this->get('/checkout/cancel')->assertRedirect();
});

test('guest ne peut pas accéder au portail de facturation', function () {
    $this->get('/billing-portal')->assertRedirect();
});

// --- Validation checkout ---

test('checkout requiert plan_id', function () {
    $this->actingAs($this->user)
        ->post('/checkout', [])
        ->assertSessionHasErrors('plan_id');
});

test('checkout valide que le plan existe', function () {
    $this->actingAs($this->user)
        ->post('/checkout', ['plan_id' => 99999])
        ->assertSessionHasErrors('plan_id');
});

test('checkout échoue si le plan n a pas de stripe_price_id', function () {
    $planSansPrix = Plan::factory()->create(['stripe_price_id' => null]);

    $this->actingAs($this->user)
        ->post('/checkout', ['plan_id' => $planSansPrix->id])
        ->assertRedirect()
        ->assertSessionHas('error');
});

// --- Pages succès et annulation ---

test('page de succès est accessible pour un utilisateur authentifié', function () {
    $this->actingAs($this->user)
        ->get('/checkout/success?session_id=test_session')
        ->assertOk();
});

test('page d annulation est accessible pour un utilisateur authentifié', function () {
    $this->actingAs($this->user)
        ->get('/checkout/cancel')
        ->assertOk();
});

// --- Webhook ---

test('webhook stripe ne retourne pas 404 en POST', function () {
    $response = $this->post('/stripe/webhook', [], ['Stripe-Signature' => 'test']);
    expect($response->status())->not->toBe(404);
});

test('webhook stripe rejette les requêtes GET', function () {
    $response = $this->get('/stripe/webhook');
    expect($response->status())->toBeIn([404, 405]);
});
