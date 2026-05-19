<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests sécurité #254 (v1.19.15) — Anti user enumeration POST /api/v1/register.
 *
 * Invariants vérifiés :
 *   - Email INEXISTANT : 201 + message générique + user créé en DB + rôle 'user'.
 *   - Email EXISTANT   : 201 + MÊME message générique + 0 doublon + mail envoyé.
 *   - DANS TOUS LES CAS : pas de token Sanctum dans la réponse, pas de leak de
 *     l'existence du compte (timing/structure/code HTTP/message).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Api\Mail\RegistrationAttemptMail;

uses(Tests\TestCase::class, RefreshDatabase::class);

const GENERIC_MESSAGE_PREFIX = 'Si l\'adresse est valide';

it('POST /api/v1/register avec email INEXISTANT crée le user et retourne 201 générique', function () {
    Mail::fake();

    $payload = [
        'name' => 'Alice Tremblay',
        'email' => 'alice.new+'.uniqid().'@laveille.ai',
        'password' => 'Sup3rS@feP4ss!2026',
        'password_confirmation' => 'Sup3rS@feP4ss!2026',
    ];

    $response = $this->postJson('/api/v1/register', $payload);

    // Réponse 201 + message générique anti-enum
    $response->assertStatus(201);
    $response->assertJsonStructure(['success', 'message']);
    expect($response->json('success'))->toBeTrue();
    expect($response->json('message'))->toStartWith(GENERIC_MESSAGE_PREFIX);

    // PAS de token Sanctum (fuite par structure) ni de bloc 'user'
    expect($response->json('data'))->toBeNull();
    expect($response->json('token'))->toBeNull();
    expect($response->json('data.token') ?? null)->toBeNull();
    expect($response->json('data.user') ?? null)->toBeNull();

    // User créé en DB avec le bon email + rôle 'user' assigné
    $user = User::where('email', $payload['email'])->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Alice Tremblay');
    expect($user->hasRole('user'))->toBeTrue();

    // Aucun mail RegistrationAttemptMail ne doit être envoyé (compte inexistant)
    Mail::assertNotSent(RegistrationAttemptMail::class);
});

it('POST /api/v1/register avec email EXISTANT NE crée pas de doublon et envoie le mail tentative', function () {
    Mail::fake();

    // Pré-existant en DB
    $existing = User::factory()->create([
        'name' => 'Bob Lévesque',
        'email' => 'bob.existing+'.uniqid().'@laveille.ai',
    ]);
    $initialCount = User::where('email', $existing->email)->count();
    expect($initialCount)->toBe(1);

    $payload = [
        'name' => 'Attaquant Anonyme',
        'email' => $existing->email,
        'password' => 'Sup3rS@feP4ss!2026',
        'password_confirmation' => 'Sup3rS@feP4ss!2026',
    ];

    $response = $this->postJson('/api/v1/register', $payload);

    // Réponse 201 + MÊME message générique que le cas "email inconnu"
    $response->assertStatus(201);
    $response->assertJsonStructure(['success', 'message']);
    expect($response->json('success'))->toBeTrue();
    expect($response->json('message'))->toStartWith(GENERIC_MESSAGE_PREFIX);

    // PAS de token Sanctum — réponse strictement identique au cas inconnu
    expect($response->json('data'))->toBeNull();
    expect($response->json('token'))->toBeNull();
    expect($response->json('data.token') ?? null)->toBeNull();
    expect($response->json('data.user') ?? null)->toBeNull();

    // AUCUN doublon créé en DB
    $finalCount = User::where('email', $existing->email)->count();
    expect($finalCount)->toBe(1);

    // Le nom du user n'a PAS été écrasé par le payload de l'attaquant
    $fresh = User::find($existing->id);
    expect($fresh->name)->toBe('Bob Lévesque');

    // Le mail RegistrationAttemptMail a bien été envoyé au propriétaire légitime
    Mail::assertSent(RegistrationAttemptMail::class, function ($mail) use ($existing) {
        return $mail->hasTo($existing->email)
            && $mail->user->id === $existing->id;
    });
    Mail::assertSentCount(1);
});
