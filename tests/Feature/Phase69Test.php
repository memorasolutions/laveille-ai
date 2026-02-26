<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\UsersTable;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    $this->actingAs($this->admin);
});

test('filtre statut actif masque les utilisateurs inactifs', function () {
    $inactive = User::factory()->create(['is_active' => false, 'name' => 'UserInactif']);

    Livewire::test(UsersTable::class)
        ->set('filterStatus', 'active')
        ->assertDontSee('UserInactif');
});

test('filtre statut inactif masque les utilisateurs actifs', function () {
    User::factory()->create(['is_active' => true, 'name' => 'UserActif']);

    Livewire::test(UsersTable::class)
        ->set('filterStatus', 'inactive')
        ->assertDontSee('UserActif');
});

test('filtre role filtre par role', function () {
    $role = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

    $userWithRole = User::factory()->create(['email' => 'editor@example.com']);
    $userWithRole->assignRole($role);

    $userWithoutRole = User::factory()->create(['email' => 'norole@example.com']);

    Livewire::test(UsersTable::class)
        ->set('filterRole', 'editor')
        ->assertSee('editor@example.com')
        ->assertDontSee('norole@example.com');
});

test('resetFilters remet les filtres à zéro', function () {
    Livewire::test(UsersTable::class)
        ->set('filterStatus', 'active')
        ->set('filterRole', 'admin')
        ->call('resetFilters')
        ->assertSet('filterStatus', '')
        ->assertSet('filterRole', '')
        ->assertSet('search', '');
});

test('composant passe les roles à la vue', function () {
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::test(UsersTable::class)
        ->assertViewHas('roles', fn ($roles) => $roles->count() >= 1);
});

test('la vue affiche les selects de filtre statut et rôle', function () {
    $this->get('/admin/users')
        ->assertOk()
        ->assertSee('Tous les statuts')
        ->assertSee('Tous les rôles');
});
