<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('auth login returns token with valid credentials', function () {
    Role::findOrCreate('user', 'web');
    $user = User::factory()->create(['password' => Hash::make('Password1')]);
    $user->assignRole('user');

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'Password1',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data' => ['user', 'token']])
        ->assertJsonPath('success', true);
});

test('auth login fails with invalid credentials', function () {
    $user = User::factory()->create(['password' => Hash::make('Password1')]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'WrongPass1',
    ]);

    $response->assertStatus(401)
        ->assertJson(['success' => false]);
});

test('auth login validates required fields', function () {
    $response = $this->postJson('/api/v1/login', []);

    $response->assertStatus(422);
});

test('auth register creates user and returns neutral 201 (anti-enumeration)', function () {
    Role::findOrCreate('user', 'web');

    $response = $this->postJson('/api/v1/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    // #254 (S99) : réponse 201 neutre, sans user ni token (pas de fuite d'énumération).
    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'message', 'data'])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    // Le compte est bien créé en base pour une adresse inconnue.
    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('auth register validates required fields', function () {
    $response = $this->postJson('/api/v1/register', []);

    $response->assertStatus(422);
});

test('auth register does not create a duplicate for an existing email (anti-enumeration)', function () {
    Role::findOrCreate('user', 'web');
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Duplicate',
        'email' => 'taken@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    // #254 (S99) : pour ne PAS révéler l'existence du compte, la réponse est la même 201
    // neutre que pour une adresse inconnue — mais aucun doublon n'est créé.
    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

test('auth logout revokes token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('auth user returns authenticated user', function () {
    Role::findOrCreate('user', 'web');
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/user');

    $response->assertStatus(200)
        ->assertJsonPath('data.email', $user->email);
});

test('auth user requires authentication', function () {
    $response = $this->getJson('/api/v1/user');

    $response->assertStatus(401);
});

test('auth logout requires authentication', function () {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401);
});
