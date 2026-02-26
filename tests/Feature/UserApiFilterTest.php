<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('user', 'web');

    $this->admin = User::factory()->create(['name' => 'Admin User']);
    $this->admin->assignRole('super_admin');
});

test('users index supports filter by name', function () {
    User::factory()->create(['name' => 'Alice Smith']);
    User::factory()->create(['name' => 'Bob Jones']);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users?filter[name]=Alice');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Alice Smith');
});

test('users index supports filter by email', function () {
    User::factory()->create(['email' => 'alice@test.com']);
    User::factory()->create(['email' => 'bob@test.com']);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users?filter[email]=alice@test.com');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.email'))->toBe('alice@test.com');
});

test('users index supports sort by name', function () {
    User::factory()->create(['name' => 'Zara']);
    User::factory()->create(['name' => 'Alpha']);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users?sort=name');

    $response->assertStatus(200);
    $names = collect($response->json('data'))->pluck('name')->values();
    expect($names->first())->toBe('Admin User');
});

test('users index supports descending sort', function () {
    User::factory()->create(['name' => 'Zara']);
    User::factory()->create(['name' => 'Alpha']);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users?sort=-name');

    $response->assertStatus(200);
    $names = collect($response->json('data'))->pluck('name')->values();
    expect($names->first())->toBe('Zara');
});

test('users index supports custom per_page', function () {
    User::factory()->count(5)->create();

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users?per_page=2');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.per_page'))->toBe(2);
});

test('users index defaults to 15 per page', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users');

    $response->assertStatus(200);
    expect($response->json('meta.per_page'))->toBe(15);
});

test('users index rejects invalid sort field', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users?sort=password');

    $response->assertStatus(400);
});

test('users index rejects invalid filter field', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/users?filter[password]=test');

    $response->assertStatus(400);
});
