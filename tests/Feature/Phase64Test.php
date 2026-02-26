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

test('composant users table se monte', function () {
    Livewire::test(UsersTable::class)
        ->assertOk()
        ->assertSee('Rechercher');
});

test('select all coche tous les utilisateurs', function () {
    User::factory()->count(3)->create(['is_active' => true]);

    $component = Livewire::test(UsersTable::class)
        ->set('selectAll', true);

    $selected = $component->get('selected');
    expect(count($selected))->toBeGreaterThanOrEqual(3);
});

test('deselect all vide la sélection', function () {
    User::factory()->count(3)->create(['is_active' => true]);

    Livewire::test(UsersTable::class)
        ->set('selectAll', true)
        ->set('selectAll', false)
        ->assertSet('selected', []);
});

test('bulk activate met is_active à true', function () {
    $user = User::factory()->create(['is_active' => false]);

    Livewire::test(UsersTable::class)
        ->set('selected', [$user->id])
        ->set('bulkAction', 'activate')
        ->call('executeBulkAction');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => true,
    ]);
});

test('bulk deactivate met is_active à false', function () {
    $user = User::factory()->create(['is_active' => true]);

    Livewire::test(UsersTable::class)
        ->set('selected', [$user->id])
        ->set('bulkAction', 'deactivate')
        ->call('executeBulkAction');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => false,
    ]);
});

test('bulk delete supprime les utilisateurs sélectionnés', function () {
    $user = User::factory()->create();

    Livewire::test(UsersTable::class)
        ->set('selected', [$user->id])
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('bulk action sans sélection ne modifie rien', function () {
    $user = User::factory()->create(['is_active' => false]);

    Livewire::test(UsersTable::class)
        ->set('selected', [])
        ->set('bulkAction', 'activate')
        ->call('executeBulkAction');

    $user->refresh();
    expect($user->is_active)->toBeFalse();
});
