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

it('la page activité retourne 200 pour un utilisateur authentifié', function () {
    $this->actingAs($this->user)->get('/user/activity')->assertStatus(200);
});

it('les invités sont redirigés vers /login', function () {
    $this->get('/user/activity')->assertRedirect('/login');
});

it('la page affiche le titre Journal d activité', function () {
    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertSee('Journal d');
});

it('la page affiche le lien retour vers le profil', function () {
    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertSee('user/profile', false);
});

it('la page affiche Aucune activité si aucune entrée', function () {
    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertSee('Aucune activité');
});

it('la page affiche les activités de l utilisateur courant', function () {
    activity()->causedBy($this->user)->log('action-test-user');

    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertSee('action-test-user');
});

it('la page n affiche pas les activités d un autre utilisateur', function () {
    $other = User::factory()->create();
    activity()->causedBy($other)->log('action-autre-utilisateur');

    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertDontSee('action-autre-utilisateur');
});

it('la page affiche le log_name de l activité', function () {
    activity('default')->causedBy($this->user)->log('une-action');

    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertSee('default');
});

it('la page affiche la description de l activité', function () {
    activity()->causedBy($this->user)->log('connexion-depuis-chrome');

    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertSee('connexion-depuis-chrome');
});

it('plusieurs activités de l utilisateur sont affichées', function () {
    activity()->causedBy($this->user)->log('action-une');
    activity()->causedBy($this->user)->log('action-deux');
    activity()->causedBy($this->user)->log('action-trois');

    $this->actingAs($this->user)
        ->get('/user/activity')
        ->assertSee('action-une')
        ->assertSee('action-deux')
        ->assertSee('action-trois');
});
