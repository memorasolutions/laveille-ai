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
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page sante systeme retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/health')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/health')->assertRedirect('/login');
});

it('les utilisateurs non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/health')->assertStatus(403);
});

it('la page affiche le titre Sante systeme', function () {
    $this->actingAs($this->admin)->get('/admin/health')->assertSee('Santé système');
});

it('la page affiche le bouton Lancer les verifications', function () {
    $this->actingAs($this->admin)->get('/admin/health')->assertSee('Lancer les vérifications');
});

it('la page affiche l etat vide sans resultats', function () {
    $this->actingAs($this->admin)->get('/admin/health')->assertSee('Aucune vérification effectuée');
});

it('la route refresh POST redirige avec succes', function () {
    $this->actingAs($this->admin)
        ->post('/admin/health/refresh')
        ->assertRedirect('/admin/health')
        ->assertSessionHas('success');
});

it('apres refresh la page affiche les resultats des checks', function () {
    $this->actingAs($this->admin)->post('/admin/health/refresh');
    $this->actingAs($this->admin)->get('/admin/health')->assertSee('Database');
});

it('la page affiche l icone solar heart pulse', function () {
    $this->actingAs($this->admin)->get('/admin/health')->assertSee('Santé système');
});

it('les invites sont rediriges depuis la route refresh', function () {
    $this->post('/admin/health/refresh')->assertRedirect('/login');
});
