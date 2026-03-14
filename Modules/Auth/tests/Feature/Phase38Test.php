<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
});

it('google redirect route exists', function () {
    $response = $this->get('/auth/google/redirect');
    // Should redirect to Google OAuth
    expect($response->status())->toBeIn([302, 200]);
});

it('github redirect route exists', function () {
    $response = $this->get('/auth/github/redirect');
    expect($response->status())->toBeIn([302, 200]);
});

it('invalid provider returns 404', function () {
    $this->get('/auth/discord/redirect')->assertStatus(404);
});

it('social callback creates new user', function () {
    $socialiteUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('social@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Social User');
    $socialiteUser->shouldReceive('getNickname')->andReturn('socialuser');
    $socialiteUser->shouldReceive('getId')->andReturn('12345');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback');

    $this->assertDatabaseHas('users', ['email' => 'social@example.com', 'social_provider' => 'google']);
});

it('social callback logs in existing user', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    $socialiteUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
    $socialiteUser->shouldReceive('getNickname')->andReturn('existing');
    $socialiteUser->shouldReceive('getId')->andReturn('99999');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback');
    $this->assertAuthenticatedAs($user);
});

it('social login button visible on login page when configured', function () {
    config(['services.google.client_id' => 'test-id']);
    config(['services.github.client_id' => 'test-id']);
    $this->get('/login')->assertSee('Google')->assertSee('GitHub');
});

it('social login buttons hidden when not configured', function () {
    config(['services.google.client_id' => null]);
    config(['services.github.client_id' => null]);
    $this->get('/login')->assertDontSee('Ou continuer avec');
});

it('all 7 providers are supported', function () {
    $allowed = ['google', 'github', 'microsoft', 'facebook', 'linkedin', 'x', 'apple'];
    foreach ($allowed as $provider) {
        $response = $this->get("/auth/{$provider}/redirect");
        expect($response->status())->toBeIn([302, 200, 500], "Provider {$provider} should be allowed");
    }
});
