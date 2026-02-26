<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

// Pricing page
it('pricing page loads successfully', function () {
    $response = $this->get('/pricing');
    $response->assertStatus(200);
    $response->assertSee('simples et transparentes');
});

it('pricing page shows plan names from database', function () {
    Plan::factory()->create(['name' => 'Free', 'slug' => 'free', 'price' => 0, 'is_active' => true]);
    Plan::factory()->create(['name' => 'Pro', 'slug' => 'pro', 'price' => 29.99, 'is_active' => true]);
    Plan::factory()->create(['name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 99.99, 'is_active' => true]);

    $response = $this->get('/pricing');
    $response->assertStatus(200)
        ->assertSee('Free')
        ->assertSee('Pro')
        ->assertSee('Enterprise');
});

it('pricing page shows feature labels in french', function () {
    Plan::factory()->create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price' => 29.99,
        'features' => ['10_users', 'priority_support', 'api_access'],
        'is_active' => true,
    ]);

    $response = $this->get('/pricing');
    $response->assertStatus(200)
        ->assertSee('10 utilisateurs')
        ->assertSee('Support prioritaire')
        ->assertSee('Accès API complet');
});

it('pricing page shows trial days', function () {
    Plan::factory()->create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price' => 29.99,
        'trial_days' => 14,
        'is_active' => true,
    ]);

    $response = $this->get('/pricing');
    $response->assertStatus(200)
        ->assertSee('14 jours', false)
        ->assertSee('essai gratuit', false);
});

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
        ->from('/pricing')
        ->post('/checkout', ['plan_id' => $plan->id]);

    $response->assertRedirect('/pricing');
    $response->assertSessionHas('error');
});
