<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page sauvegardes retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/backups')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/backups')->assertRedirect('/login');
});

it('les utilisateurs non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/backups')->assertStatus(403);
});

it('la page affiche le titre Sauvegardes', function () {
    $this->actingAs($this->admin)->get('/admin/backups')->assertSee('Sauvegardes');
});

it('la page affiche le bouton Lancer une sauvegarde', function () {
    $this->actingAs($this->admin)->get('/admin/backups')->assertSee('Lancer une sauvegarde');
});

it('la page affiche l etat vide', function () {
    Storage::fake('local');
    $this->actingAs($this->admin)->get('/admin/backups')->assertSee('Aucune sauvegarde disponible');
});

it('la note info spatie est affichee', function () {
    $this->actingAs($this->admin)->get('/admin/backups')->assertSee('spatie/laravel-backup', false);
});

it('l icone backup est affichee', function () {
    $this->actingAs($this->admin)->get('/admin/backups')->assertSee('Sauvegardes');
});

it('la route run POST redirige avec succes', function () {
    $this->actingAs($this->admin)->post('/admin/backups/run')->assertRedirect()->assertSessionHas('success');
});

it('la route delete redirige', function () {
    $this->actingAs($this->admin)->delete('/admin/backups/delete', ['path' => 'fake.zip'])->assertRedirect();
});
