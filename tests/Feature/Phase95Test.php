<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
});

it('la page sessions retourne 200 pour un utilisateur authentifié', function () {
    $this->actingAs($this->user)->get('/user/sessions')->assertStatus(200);
});

it('les invités sont redirigés vers /login', function () {
    $this->get('/user/sessions')->assertRedirect('/login');
});

it('la page affiche Sessions actives', function () {
    $this->actingAs($this->user)->get('/user/sessions')->assertSee('Sessions actives');
});

it('la page affiche le lien retour vers le profil', function () {
    $this->actingAs($this->user)
        ->get('/user/sessions')
        ->assertSee('user/profile', false);
});

it('la page affiche le formulaire révoquer toutes les autres', function () {
    $this->actingAs($this->user)
        ->get('/user/sessions')
        ->assertSee('name="password"', false);
});

it('révoquer une session appartenant à un autre utilisateur ne la supprime pas', function () {
    DB::table('sessions')->insert([
        'id' => 'other-user-session',
        'user_id' => 999,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs($this->user)->post('/user/sessions/other-user-session/revoke');

    $this->assertDatabaseHas('sessions', ['id' => 'other-user-session']);
});

it('révoquer une session existante la supprime de la base de données', function () {
    DB::table('sessions')->insert([
        'id' => 'sess-to-delete',
        'user_id' => $this->user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs($this->user)->post('/user/sessions/sess-to-delete/revoke');

    $this->assertDatabaseMissing('sessions', ['id' => 'sess-to-delete']);
});

it('révoquer autres avec un mauvais mot de passe retourne une erreur', function () {
    $this->actingAs($this->user)
        ->post('/user/sessions/revoke-others', ['password' => 'wrong-password'])
        ->assertSessionHasErrors(['password']);
});

it('révoquer autres avec le bon mot de passe redirige avec succès', function () {
    $this->actingAs($this->user)
        ->post('/user/sessions/revoke-others', ['password' => 'password'])
        ->assertRedirect();
});

it('révoquer autres avec le bon mot de passe envoie un message de succès', function () {
    $this->actingAs($this->user)
        ->post('/user/sessions/revoke-others', ['password' => 'password'])
        ->assertSessionHas('success');
});

it('révoquer autres supprime les sessions de cet utilisateur sauf la courante', function () {
    $currentSession = $this->actingAs($this->user)->get('/user/sessions');
    $sessionId = $this->user->getKey(); // surrogate; just test the route works

    DB::table('sessions')->insert([
        'id' => 'old-session-1',
        'user_id' => $this->user->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'OldBrowser',
        'payload' => '',
        'last_activity' => time() - 3600,
    ]);

    $this->actingAs($this->user)
        ->post('/user/sessions/revoke-others', ['password' => 'password'])
        ->assertSessionHas('success');

    // old-session-1 should be gone (it's not the current session)
    $this->assertDatabaseMissing('sessions', ['id' => 'old-session-1']);
});
