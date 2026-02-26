<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

it('download retourne 200 avec Content-Disposition pour admin', function () {
    Storage::fake('local');
    Storage::disk('local')->put('Laravel Backup/test.zip', 'dummy content');

    $response = $this->actingAs($this->admin)
        ->get('/admin/backups/download?path=Laravel Backup/test.zip');

    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition');
});

it('download redirige les invités vers login', function () {
    $this->get('/admin/backups/download?path=test.zip')
        ->assertRedirect('/login');
});

it('download retourne 403 pour non-admin', function () {
    $this->actingAs($this->user)
        ->get('/admin/backups/download?path=test.zip')
        ->assertStatus(403);
});

it('download retourne 404 si fichier inexistant', function () {
    Storage::fake('local');

    $this->actingAs($this->admin)
        ->get('/admin/backups/download?path=inexistant.zip')
        ->assertStatus(404);
});

it('la page backups affiche le bouton Télécharger pour admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/backups')
        ->assertSee('Télécharger');
});

it('la route admin.backups.download existe', function () {
    expect(Route::has('admin.backups.download'))->toBeTrue();
});
