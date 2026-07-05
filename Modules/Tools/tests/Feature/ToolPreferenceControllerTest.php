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

it('saves and retrieves custom_durations for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_durations',
        'value' => [32, 90],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['custom_durations' => [32, 90]]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertJson(['preferences' => ['custom_durations' => [32, 90]]]);
});

it('caps custom_durations at 2 entries and filters out-of-range values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_durations',
        'value' => [32, 0, 999, 90, 45],
    ]);

    $response->assertOk();
    $durations = $response->json('preferences.custom_durations');
    expect($durations)->toHaveCount(2);
    expect($durations)->not->toContain(0);
    expect($durations)->not->toContain(999);
});

it('saves and retrieves favorite_colors for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'favorite_colors',
        'value' => ['#E8A9AE', '#DCC3A0'],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['favorite_colors' => ['#E8A9AE', '#DCC3A0']]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertJson(['preferences' => ['favorite_colors' => ['#E8A9AE', '#DCC3A0']]]);
});

it('caps favorite_colors at 2 entries and filters invalid hex values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'favorite_colors',
        'value' => ['#111111', 'not-a-color', '#222222', '#333333'],
    ]);

    $response->assertOk();
    $colors = $response->json('preferences.favorite_colors');
    expect($colors)->toHaveCount(2);
    expect($colors)->not->toContain('not-a-color');
});

it('saves and retrieves traffic_thresholds for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'traffic_thresholds',
        'value' => ['green' => 70, 'yellow' => 40],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['traffic_thresholds' => ['green' => 70, 'yellow' => 40]]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertJson(['preferences' => ['traffic_thresholds' => ['green' => 70, 'yellow' => 40]]]);
});

it('rejects invalid traffic_thresholds (yellow must be less than green)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'traffic_thresholds',
        'value' => ['green' => 20, 'yellow' => 50],
    ]);

    $response->assertStatus(422);
});

it('rejects traffic_thresholds missing required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'traffic_thresholds',
        'value' => ['green' => 70],
    ]);

    $response->assertStatus(422);
});

it('rejects an unknown preference key format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'Invalid Key!',
        'value' => ['#111111'],
    ]);

    $response->assertStatus(422);
});
