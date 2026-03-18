<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\SaaS\Models\UsageRecord;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('GET /usage/current returns usage for authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UsageRecord::create([
        'user_id' => $user->id,
        'metric' => 'api_calls',
        'quantity' => 10,
        'recorded_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/usage/current?metric=api_calls');

    $response->assertOk()->assertJson([
        'metric' => 'api_calls',
        'usage' => 10,
        'limit' => null,
        'remaining' => null,
    ]);
});

test('GET /usage/current requires metric parameter', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/usage/current')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['metric']);
});

test('GET /usage/summary returns metrics grouped by name', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UsageRecord::create(['user_id' => $user->id, 'metric' => 'api_calls', 'quantity' => 5, 'recorded_at' => now()]);
    UsageRecord::create(['user_id' => $user->id, 'metric' => 'api_calls', 'quantity' => 3, 'recorded_at' => now()]);
    UsageRecord::create(['user_id' => $user->id, 'metric' => 'storage', 'quantity' => 100, 'recorded_at' => now()]);

    $response = $this->getJson('/api/v1/usage/summary');

    $response->assertOk()
        ->assertJsonStructure(['period' => ['from', 'to'], 'metrics'])
        ->assertJsonPath('metrics.api_calls', 8)
        ->assertJsonPath('metrics.storage', 100);
});

test('GET /usage/daily returns daily breakdown with correct count', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    UsageRecord::create(['user_id' => $user->id, 'metric' => 'api_calls', 'quantity' => 5, 'recorded_at' => now()->subDays(2)]);
    UsageRecord::create(['user_id' => $user->id, 'metric' => 'api_calls', 'quantity' => 3, 'recorded_at' => now()]);

    $response = $this->getJson('/api/v1/usage/daily?metric=api_calls&days=7');

    $response->assertOk()
        ->assertJsonPath('metric', 'api_calls')
        ->assertJsonPath('days', 7);

    $data = $response->json('data');
    expect($data)->toHaveCount(7);
    expect($data[now()->format('Y-m-d')])->toBe(3);
    expect($data[now()->subDays(2)->format('Y-m-d')])->toBe(5);
});

test('POST /usage/record creates a new usage record', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/usage/record', [
        'metric' => 'api_calls',
        'quantity' => 5,
    ]);

    $response->assertCreated()->assertJsonStructure(['record']);
    $this->assertDatabaseHas('usage_records', [
        'user_id' => $user->id,
        'metric' => 'api_calls',
        'quantity' => 5,
    ]);
});

test('unauthenticated requests return 401', function () {
    $this->getJson('/api/v1/usage/current?metric=api_calls')->assertUnauthorized();
    $this->getJson('/api/v1/usage/summary')->assertUnauthorized();
    $this->getJson('/api/v1/usage/daily?metric=api_calls&days=7')->assertUnauthorized();
    $this->postJson('/api/v1/usage/record', ['metric' => 'x', 'quantity' => 1])->assertUnauthorized();
});
