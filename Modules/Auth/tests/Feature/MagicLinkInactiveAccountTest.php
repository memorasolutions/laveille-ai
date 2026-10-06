<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * La connexion par code (magic link / OTP) doit refuser un compte désactivé ou verrouillé,
 * avec le même échec qu'un code invalide (web et API passent par MagicLinkService::verify).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Services\MagicLinkService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('refuse le code d\'un compte désactivé', function () {
    $user = User::factory()->create(['is_active' => false]);
    $token = app(MagicLinkService::class)->generate($user->email)['token'];

    expect(app(MagicLinkService::class)->verify($user->email, $token))->toBeNull();
    $this->post('/magic-link/verify', ['email' => $user->email, 'token' => $token])->assertSessionHasErrors('token');
    $this->assertGuest();
});

it('refuse le code d\'un compte verrouillé', function () {
    $user = User::factory()->create(['is_active' => true, 'locked_until' => now()->addHour()]);
    $token = app(MagicLinkService::class)->generate($user->email)['token'];

    expect(app(MagicLinkService::class)->verify($user->email, $token))->toBeNull();
    $this->assertGuest();
});

it('refuse aussi par l\'API le code d\'un compte désactivé', function () {
    $user = User::factory()->create(['is_active' => false]);
    $token = app(MagicLinkService::class)->generate($user->email)['token'];

    $this->postJson('/api/magic-link/verify', ['email' => $user->email, 'token' => $token])->assertStatus(422);
    $this->assertGuest();
});

it('accepte le code d\'un compte actif', function () {
    $user = User::factory()->create(['is_active' => true, 'locked_until' => null]);
    $token = app(MagicLinkService::class)->generate($user->email)['token'];

    expect(app(MagicLinkService::class)->verify($user->email, $token)?->id)->toBe($user->id);
});
