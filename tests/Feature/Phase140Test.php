<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

test('auth user sees subscription link on pricing for paid plan without stripe_price_id', function () {
    $user = User::factory()->create();
    Plan::factory()->create(['price' => 29, 'is_active' => true, 'stripe_price_id' => null]);

    $this->actingAs($user)
        ->get(route('pricing'))
        ->assertOk()
        ->assertSee('Gérer mon abonnement');
});

test('guest sees register link on pricing for paid plan', function () {
    Plan::factory()->create(['price' => 29, 'is_active' => true]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Choisir ce plan');
});

test('landing page passes recentPosts variable with published articles', function () {
    Article::factory()->count(2)->create([
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertViewHas('recentPosts');
});

test('pricing FAQ has correct bootstrap collapse markup', function () {
    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('data-bs-toggle="collapse"', false);
});

test('pricing CTA section renders contactez-nous', function () {
    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Contactez-nous');
});

test('gosass layout includes bootstrap bundle script', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('bootstrap.bundle.min.js', false);
});

test('auth user on landing sees subscription link for paid plan', function () {
    $user = User::factory()->create();
    Plan::factory()->create(['price' => 29, 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Gérer mon abonnement');
});
