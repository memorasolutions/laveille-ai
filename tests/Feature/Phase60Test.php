<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

it('landing page se charge correctement', function () {
    $this->get('/')
        ->assertStatus(200);
});

it('landing affiche les plans actifs', function () {
    Plan::factory()->count(2)->create(['is_active' => true]);
    Plan::factory()->create(['is_active' => false]);

    $this->get('/')
        ->assertViewHas('plans', fn ($plans) => $plans->count() === 2);
});

it('landing affiche les 3 derniers articles', function () {
    Article::factory()->published()->count(5)->create();

    $this->get('/')
        ->assertViewHas('recentPosts', fn ($articles) => $articles->count() === 3);
});

it('landing affiche le nom de l application', function () {
    $this->get('/')
        ->assertSee(config('app.name'));
});

it('landing route a un nom home', function () {
    expect(route('home'))->toBe(url('/'));
});
