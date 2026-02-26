<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
});

it('la page confirm-password retourne 200 pour un utilisateur authentifié', function () {
    $response = $this->actingAs($this->user)->get('/confirm-password');
    $response->assertStatus(200);
});

it('les invités sont redirigés vers /login', function () {
    $response = $this->get('/confirm-password');
    $response->assertRedirect('/login');
});

it('la page affiche le titre Confirmation requise', function () {
    $response = $this->actingAs($this->user)->get('/confirm-password');
    $response->assertSee('Confirmation requise');
});

it('la page affiche un champ mot de passe', function () {
    $response = $this->actingAs($this->user)->get('/confirm-password');
    $response->assertSee('name="password"', false);
});

it('un mauvais mot de passe retourne une erreur', function () {
    $response = $this->actingAs($this->user)->post('/confirm-password', [
        'password' => 'wrong-password',
    ]);
    $response->assertSessionHasErrors(['password']);
});

it('le bon mot de passe confirme la session', function () {
    $response = $this->actingAs($this->user)->post('/confirm-password', [
        'password' => 'password',
    ]);
    $response->assertSessionHas('auth.password_confirmed_at');
});

it('après confirmation le redirect va vers user.dashboard', function () {
    $response = $this->actingAs($this->user)->post('/confirm-password', [
        'password' => 'password',
    ]);
    $response->assertRedirect(route('user.dashboard'));
});

it('passwordConfirmed est stocké dans la session après succès', function () {
    $this->actingAs($this->user)->post('/confirm-password', [
        'password' => 'password',
    ]);
    $this->assertNotNull(session('auth.password_confirmed_at'));
});

it('le formulaire contient un token CSRF', function () {
    $response = $this->actingAs($this->user)->get('/confirm-password');
    $response->assertSee('_token', false);
});

it('la page affiche un lien retour vers le dashboard', function () {
    $response = $this->actingAs($this->user)->get('/confirm-password');
    $response->assertSee('Retour au tableau de bord');
});
