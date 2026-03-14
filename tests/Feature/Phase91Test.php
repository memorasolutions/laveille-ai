<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
});

it('api tokens page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('user.api-tokens'))
        ->assertStatus(200);
});

it('api tokens page redirects guests', function () {
    $this->get(route('user.api-tokens'))
        ->assertRedirect(route('login'));
});

it('user can create api token', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => 'Mon token'])
        ->assertRedirect();
});

it('token is stored in database after creation', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => 'Mon token']);

    $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Mon token']);
});

it('token value is flashed in session after creation', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => 'Mon token'])
        ->assertSessionHas('token_value');
});

it('token creation requires a name', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), [])
        ->assertSessionHasErrors('name');
});

it('token name max 255 chars', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => str_repeat('a', 256)])
        ->assertSessionHasErrors('name');
});

it('user can revoke a token', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => 'Token to revoke']);

    $token = $this->user->tokens()->first();

    $this->actingAs($this->user)
        ->delete(route('user.api-tokens.destroy', $token->id))
        ->assertRedirect();
});

it('revoked token is removed from database', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => 'Will be revoked']);

    $token = $this->user->tokens()->first();

    $this->actingAs($this->user)
        ->delete(route('user.api-tokens.destroy', $token->id));

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('user cannot revoke another users token', function () {
    $otherUser = User::factory()->create();
    $otherUser->createToken('other token');
    $otherToken = $otherUser->tokens()->first();

    $this->actingAs($this->user)
        ->delete(route('user.api-tokens.destroy', $otherToken->id));

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
});

it('api tokens page shows token name', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => 'Mon super token']);

    $this->actingAs($this->user)
        ->get(route('user.api-tokens'))
        ->assertSee('Mon super token');
});

it('api tokens page shows empty message when no tokens', function () {
    $this->actingAs($this->user)
        ->get(route('user.api-tokens'))
        ->assertSee('Aucun token');
});

it('api tokens page shows last used at column', function () {
    $this->actingAs($this->user)
        ->post(route('user.api-tokens.store'), ['name' => 'Test token']);

    $this->actingAs($this->user)
        ->get(route('user.api-tokens'))
        ->assertSee('Jamais');
});
