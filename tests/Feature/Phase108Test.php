<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\TranslationsManager;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page traductions retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/translations')->assertStatus(200);
});

it('les invités sont redirigés vers login', function () {
    $this->get('/admin/translations')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/translations')->assertStatus(403);
});

it('la page affiche les onglets fr et en', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->assertSee('FR')
        ->assertSee('EN');
});

it('la page affiche les clés de traduction', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->set('search', 'Login')
        ->assertSee('Login');
});

it('la page affiche le compteur de clés', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->assertSee('traduites');
});

it('update met à jour une clé de traduction via livewire', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->call('updateTranslation', 'Login', 'Updated Login')
        ->assertDispatched('toast');
});

it('update valide que la clé est requise pour addKey', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->set('newKey', '')
        ->call('addKey')
        ->assertHasErrors(['newKey']);
});

it('destroy supprime une clé de traduction via livewire', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->call('deleteKey', 'Login')
        ->assertDispatched('toast');
});

it('switch locale en affiche les traductions anglaises', function () {
    $this->actingAs($this->admin);

    Livewire::test(TranslationsManager::class)
        ->set('targetLocale', 'en')
        ->set('search', 'Welcome')
        ->assertSee('Welcome');
});

it('la page affiche le titre Traductions', function () {
    $this->actingAs($this->admin)->get('/admin/translations')
        ->assertSee('Traductions');
});
