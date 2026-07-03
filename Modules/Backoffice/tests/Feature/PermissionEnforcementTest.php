<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Preuve négative de l'audit de sécurité du 2026-07-03 (OWASP A01) : un
 * utilisateur avec un accès admin générique (view_admin_panel) mais SANS la
 * permission granulaire de mutation doit se faire bloquer par les composants
 * Livewire, pas seulement par les routes HTTP. Représentatif des 15 fichiers
 * corrigés le même jour (voir config/version.php v1.76.3).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\UsersTable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeRestrictedAdmin(string ...$permissions): User
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'restricted_admin_test', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view_admin_panel', 'guard_name' => 'web']));
    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $user->assignRole($role);

    return $user;
}

test('UsersTable::toggleActive bloque un admin sans update_users', function () {
    $admin = makeRestrictedAdmin('view_users');
    $target = User::factory()->create(['is_active' => true]);
    $this->actingAs($admin);

    Livewire::test(UsersTable::class)
        ->call('toggleActive', $target->id)
        ->assertForbidden();

    expect($target->fresh()->is_active)->toBeTrue();
});

test('UsersTable::toggleActive fonctionne pour un admin avec update_users', function () {
    $admin = makeRestrictedAdmin('view_users', 'update_users');
    $target = User::factory()->create(['is_active' => true]);
    $this->actingAs($admin);

    Livewire::test(UsersTable::class)->call('toggleActive', $target->id);

    expect($target->fresh()->is_active)->toBeFalse();
});
