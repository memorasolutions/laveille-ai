<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Le middleware EnsureAccountActive ferme TOUTES les voies d'un compte désactivé ou verrouillé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('déconnecte et redirige un compte désactivé à session ouverte', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('déconnecte un compte verrouillé de la même façon', function () {
    $user = User::factory()->create(['is_active' => true, 'locked_until' => now()->addHour()]);

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('refuse en 403 un compte désactivé sur l\'API (session Sanctum)', function () {
    $user = User::factory()->create(['is_active' => false]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/user')->assertStatus(403);
});

it('refuse en 403 un jeton Bearer réel d\'un compte désactivé et le révoque', function () {
    $user = User::factory()->create(['is_active' => true]);
    $token = $user->createToken('t')->plainTextToken;
    $user->forceFill(['is_active' => false])->save();

    $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/user')->assertStatus(403);
    expect($user->tokens()->count())->toBe(0);
});

it('laisse passer un compte actif sans entrave', function () {
    $user = User::factory()->create(['is_active' => true, 'locked_until' => null]);

    $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    $this->assertAuthenticatedAs($user);
});

it('éjecte à la requête suivante un compte désactivé connecté par Auth::login (flux social)', function () {
    $user = User::factory()->create(['is_active' => false]);
    Auth::login($user);

    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('laisse la page de connexion accessible après éjection (pas de boucle)', function () {
    $this->get(route('login'))->assertStatus(200);
});
