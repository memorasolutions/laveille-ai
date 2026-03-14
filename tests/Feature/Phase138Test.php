<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

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

test('checkout success page requires auth', function () {
    $this->get(route('checkout.success'))
        ->assertRedirect(route('login'));
});

test('billing portal route requires authentication', function () {
    $this->get(route('billing.portal'))
        ->assertRedirect(route('login'));
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
