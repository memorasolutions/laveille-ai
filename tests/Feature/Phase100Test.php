<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page journaux retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/logs')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/logs')->assertRedirect('/login');
});

it('les utilisateurs non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/logs')->assertStatus(403);
});

it('la page affiche le titre Journaux', function () {
    $this->actingAs($this->admin)->get('/admin/logs')->assertSee('Journaux', false);
});

it('la page affiche le bouton Vider les journaux', function () {
    $this->actingAs($this->admin)->get('/admin/logs')->assertSee('Vider les journaux');
});

it('la page affiche les filtres de niveau', function () {
    $this->actingAs($this->admin)->get('/admin/logs')
        ->assertSee('All')
        ->assertSee('Error')
        ->assertSee('Warning');
});

it('la page affiche l etat vide si aucune entree', function () {
    File::put(storage_path('logs/laravel.log'), '');
    $this->actingAs($this->admin)->get('/admin/logs')->assertSee('Aucune entrée de journal');
});

it('la page affiche les entrees de log si le fichier contient des lignes', function () {
    $logLine = '[2026-02-19 12:00:00] local.ERROR: Une erreur de test {}'.PHP_EOL;
    File::put(storage_path('logs/laravel.log'), $logLine);

    $this->actingAs($this->admin)->get('/admin/logs')
        ->assertSee('ERROR')
        ->assertSee('Une erreur de test');
});

it('la route clear POST vide les journaux et redirige', function () {
    File::put(storage_path('logs/laravel.log'), '[2026-02-19 12:00:00] local.ERROR: test {}');

    $this->actingAs($this->admin)->post('/admin/logs/clear')
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(file_get_contents(storage_path('logs/laravel.log')))->toBe('');
});

it('le filtre level filtre les entrees', function () {
    $logContent = '[2026-02-19 12:00:00] local.ERROR: Erreur critique {}'.PHP_EOL
        .'[2026-02-19 12:01:00] local.INFO: Info message {}'.PHP_EOL;
    File::put(storage_path('logs/laravel.log'), $logContent);

    $this->actingAs($this->admin)->get('/admin/logs?level=error')
        ->assertSee('ERROR')
        ->assertDontSee('INFO');
});
