<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Http\Controllers\CheckoutController;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

test('checkout route requires authentication', function () {
    $response = $this->post(route('checkout'), ['plan_id' => 1]);
    $response->assertRedirect(route('login'));
});

test('checkout rejects invalid plan_id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('checkout'), ['plan_id' => 99999])
        ->assertSessionHasErrors('plan_id');
});

test('checkout rejects plan without stripe_price_id', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['stripe_price_id' => null, 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('checkout'), ['plan_id' => $plan->id])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('checkout success page accessible when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('checkout.success'))
        ->assertOk()
        ->assertSee('Merci');
});

test('checkout success page requires auth', function () {
    $this->get(route('checkout.success'))
        ->assertRedirect(route('login'));
});

test('checkout cancel page accessible when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('checkout.cancel'))
        ->assertOk()
        ->assertSee('annulé');
});

test('billing portal route requires authentication', function () {
    $this->get(route('billing.portal'))
        ->assertRedirect(route('login'));
});

test('pricing page displays plans from database', function () {
    Plan::factory()->create(['name' => 'Plan Test Alpha', 'is_active' => true, 'price' => 29]);
    Plan::factory()->create(['name' => 'Plan Test Beta', 'is_active' => true, 'price' => 99]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Plan Test Alpha')
        ->assertSee('Plan Test Beta');
});

test('pricing page shows checkout form for authenticated user with paid plan', function () {
    $user = User::factory()->create();
    Plan::factory()->create([
        'name' => 'Pro Test',
        'is_active' => true,
        'price' => 29,
        'stripe_price_id' => 'price_test_123',
    ]);

    $response = $this->actingAs($user)->get(route('pricing'));

    $response->assertOk();
    $response->assertSee('checkout');
    $response->assertSee('plan_id');
});

test('pricing page shows register link for guests with paid plan', function () {
    Plan::factory()->create([
        'name' => 'Pro Guest',
        'is_active' => true,
        'price' => 29,
        'stripe_price_id' => 'price_test_456',
    ]);

    $response = $this->get(route('pricing'));

    $response->assertOk();
    $response->assertSee(route('register'));
});

test('landing page displays pricing section with plans', function () {
    Plan::factory()->create(['name' => 'Landing Plan A', 'is_active' => true, 'price' => 0]);
    Plan::factory()->create(['name' => 'Landing Plan B', 'is_active' => true, 'price' => 49]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Landing Plan A')
        ->assertSee('Landing Plan B');
});

test('checkout controller class exists and is instantiable', function () {
    expect(class_exists(CheckoutController::class))->toBeTrue();
});

test('saas config has required keys', function () {
    expect(config('saas.stripe'))->not->toBeNull();
    expect(config('saas.currency'))->not->toBeNull();
    expect(config('saas.trial_days'))->not->toBeNull();
    expect(config('saas.plans'))->not->toBeNull();
});
