<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('subscription page loads', function () {
    $this->get(route('user.subscription'))
        ->assertStatus(200)
        ->assertSee('Mon abonnement');
});

it('free plan badge shown', function () {
    $this->get(route('user.subscription'))
        ->assertSee('Plan Free');
});

it('free plan shows gratuit', function () {
    $this->get(route('user.subscription'))
        ->assertSee('Gratuit');
});

it('plans comparison grid is shown', function () {
    $this->get(route('user.subscription'))
        ->assertSee('Pro')
        ->assertSee('Enterprise');
});

it('page shows plan actuel section', function () {
    $this->get(route('user.subscription'))
        ->assertSee('Plan actuel');
});

it('page shows comparer les plans section', function () {
    $this->get(route('user.subscription'))
        ->assertSee('Comparer les plans');
});

it('nav shows abonnement link on dashboard', function () {
    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('Abonnement');
});

it('unauthenticated redirect from subscription', function () {
    auth()->logout();
    $this->get(route('user.subscription'))
        ->assertRedirect(route('login'));
});
