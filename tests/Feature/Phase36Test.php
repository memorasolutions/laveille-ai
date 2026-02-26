<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Services\MagicLinkService;

uses(RefreshDatabase::class);

test('magic_login_tokens table exists', function () {
    expect(\Illuminate\Support\Facades\Schema::hasTable('magic_login_tokens'))->toBeTrue();
});

test('magic link request page loads', function () {
    $this->get('/magic-link')->assertOk();
});

test('magic link verify page loads', function () {
    $this->get('/magic-link/verify?email=test@test.com')->assertOk();
});

test('magic link service generates 6-char token', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $result = $service->generate($user->email);

    expect($result['token'])->toHaveLength(6)
        ->and($result['expires_at'])->not->toBeNull();

    $this->assertDatabaseHas('magic_login_tokens', [
        'email' => $user->email,
        'token' => $result['token'],
    ]);
});

test('magic link service token is uppercase alphanumeric', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $result = $service->generate($user->email);

    expect($result['token'])->toMatch('/^[A-Z0-9]{6}$/');
});

test('magic link service deletes old token on regenerate', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $service->generate($user->email);
    $service->generate($user->email);

    $count = DB::table('magic_login_tokens')->where('email', $user->email)->count();
    expect($count)->toBe(1);
});

test('magic link service verifies valid token', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $result = $service->generate($user->email);

    $found = $service->verify($user->email, $result['token']);
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($user->id);
});

test('magic link service rejects wrong token', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $service->generate($user->email);

    $found = $service->verify($user->email, 'WRONG1');
    expect($found)->toBeNull();
});

test('magic link service rejects expired token', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $result = $service->generate($user->email);

    DB::table('magic_login_tokens')
        ->where('email', $user->email)
        ->update(['expires_at' => now()->subMinute()]);

    $found = $service->verify($user->email, $result['token']);
    expect($found)->toBeNull();
});

test('magic link service hasValidToken returns true', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $service->generate($user->email);

    expect($service->hasValidToken($user->email))->toBeTrue();
});

test('magic link service cleanup deletes expired tokens', function () {
    DB::table('magic_login_tokens')->insert([
        'email' => 'test@test.com',
        'token' => 'ABCDEF',
        'expires_at' => now()->subHour(),
        'used' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(MagicLinkService::class);
    $deleted = $service->cleanup();
    expect($deleted)->toBeGreaterThan(0);
});

test('send magic link validates email exists', function () {
    $this->post('/magic-link', ['email' => 'nonexistent@test.com'])
        ->assertSessionHasErrors('email');
});

test('send magic link succeeds for valid user', function () {
    $user = User::factory()->create();
    $this->post('/magic-link', ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');
});

test('magic link verify logs in user with correct token', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $result = $service->generate($user->email);

    $this->post('/magic-link/verify', [
        'email' => $user->email,
        'token' => $result['token'],
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

test('magic link verify rejects wrong token', function () {
    $user = User::factory()->create();
    $service = app(MagicLinkService::class);
    $service->generate($user->email);

    $this->post('/magic-link/verify', [
        'email' => $user->email,
        'token' => 'WRONG1',
    ])->assertSessionHasErrors('token');

    $this->assertGuest();
});
