<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

// Sitemap
it('sitemap route returns xml content', function () {
    $response = $this->get('/sitemap.xml');
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/xml');
});

// Checkout auth protection
it('checkout requires authentication', function () {
    $response = $this->post('/checkout');
    $response->assertRedirect('/login');
});

it('checkout validates plan_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/checkout', ['plan_id' => 99999]);
    $response->assertSessionHasErrors('plan_id');
});

it('billing portal requires authentication', function () {
    $response = $this->get('/billing-portal');
    $response->assertRedirect('/login');
});

it('checkout success page requires authentication', function () {
    $response = $this->get('/checkout/success');
    $response->assertRedirect('/login');
});

it('checkout cancel page requires authentication', function () {
    $response = $this->get('/checkout/cancel');
    $response->assertRedirect('/login');
});

// Stripe webhook
it('stripe webhook route exists and does not return 404', function () {
    $response = $this->postJson('/stripe/webhook', []);
    expect($response->getStatusCode())->not->toBe(404);
});

// Middleware
it('recaptcha middleware alias is registered', function () {
    $router = app('router');
    $aliases = $router->getMiddleware();
    expect($aliases)->toHaveKey('recaptcha');
});

// Billable trait
it('user model uses billable trait', function () {
    $traits = class_uses_recursive(User::class);
    expect($traits)->toContain(\Laravel\Cashier\Billable::class);
});

// Checkout with plan without stripe_price_id
it('checkout redirects back when plan has no stripe_price_id', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'name' => 'Test',
        'slug' => 'test',
        'price' => 10,
        'stripe_price_id' => null,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->post('/checkout', ['plan_id' => $plan->id]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
