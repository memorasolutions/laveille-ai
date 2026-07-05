<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('blocks guests from tool-preferences endpoints', function () {
    $this->get('/api/tool-preferences/minuteur-visuel')->assertUnauthorized();
    $this->postJson('/api/tool-preferences/minuteur-visuel', ['key' => 'custom_colors', 'value' => ['#991B1B']])
        ->assertUnauthorized();
});

it('returns an empty array when the authenticated user has no saved preferences yet', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');

    $response->assertOk();
    $response->assertJson(['preferences' => []]);
});

it('saves and retrieves recent custom colors for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_colors',
        'value' => ['#ABCDEF', '#123456'],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['custom_colors' => ['#ABCDEF', '#123456']]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertOk();
    $show->assertJson(['preferences' => ['custom_colors' => ['#ABCDEF', '#123456']]]);
});

it('caps custom_colors at 5 entries and filters invalid hex values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_colors',
        'value' => ['#111111', 'not-a-color', '#222222', '#333333', '#444444', '#555555', '#666666'],
    ]);

    $response->assertOk();
    $colors = $response->json('preferences.custom_colors');
    expect($colors)->toHaveCount(5);
    expect($colors)->not->toContain('not-a-color');
});

it('rejects an unknown preference key format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'Invalid Key!',
        'value' => ['#111111'],
    ]);

    $response->assertStatus(422);
});
