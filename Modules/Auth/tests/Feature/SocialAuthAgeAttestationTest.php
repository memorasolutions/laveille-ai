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
});

function mockSocialiteUserForAgeAttestationTest(string $email, string $id = '77777'): void
{
    $socialiteUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn($email);
    $socialiteUser->shouldReceive('getName')->andReturn('Nouvel Utilisateur');
    $socialiteUser->shouldReceive('getNickname')->andReturn('nouvel-utilisateur');
    $socialiteUser->shouldReceive('getId')->andReturn($id);
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

test('social callback for a brand new user redirects to the age attestation screen without creating the account', function () {
    mockSocialiteUserForAgeAttestationTest('nouveau@example.com');

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('social.finalize'));
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'nouveau@example.com']);
    expect(session('social_registration.email'))->toBe('nouveau@example.com');
});

test('submitting the finalize form without checking the attestation is refused and creates no account', function () {
    mockSocialiteUserForAgeAttestationTest('sanscoche@example.com');
    $this->get('/auth/google/callback');

    $response = $this->post('/auth/finalize', []);

    $response->assertSessionHasErrors(['age_attested']);
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'sanscoche@example.com']);
});

test('submitting the finalize form with the attestation checked creates the account and logs the user in', function () {
    mockSocialiteUserForAgeAttestationTest('aveccoche@example.com');
    $this->get('/auth/google/callback');

    $response = $this->post('/auth/finalize', ['age_attested' => '1']);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['email' => 'aveccoche@example.com', 'social_provider' => 'google']);
    $this->assertAuthenticated();
    expect(session('social_registration'))->toBeNull();
});

test('social callback for an existing social user logs in directly without friction', function () {
    $user = User::factory()->create(['email' => 'existant@example.com']);

    mockSocialiteUserForAgeAttestationTest('existant@example.com', '99999');

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect($user->homeRoute());
    $this->assertAuthenticatedAs($user);
});
