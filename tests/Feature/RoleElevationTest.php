<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('editor with create_roles cannot assign permissions they dont have', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $editor->givePermissionTo('create_roles');

    // Editor n'a pas manage_backups (pas dans son rôle)
    $manageBackupsId = Permission::where('name', 'manage_backups')->value('id');

    $response = $this->actingAs($editor)
        ->post(route('admin.roles.store'), [
            'name' => 'role_eleve',
            'level' => 10,
            'permissions' => [$manageBackupsId],
        ]);

    $response->assertForbidden();
});

test('admin cannot create role with higher level than own', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('create_roles');

    $response = $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'role_superieur',
            'level' => 90,
            'permissions' => [],
        ]);

    $response->assertForbidden();
});

test('admin can create role with same or lower level and owned permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('create_roles');

    $viewArticlesId = Permission::where('name', 'view_articles')->value('id');

    $response = $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'role_inferieur',
            'level' => 50,
            'permissions' => [$viewArticlesId],
        ]);

    $response->assertRedirect(route('admin.roles.index'));
    $this->assertDatabaseHas('roles', ['name' => 'role_inferieur', 'level' => 50]);
});

test('super_admin can create any role with any level and permissions', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $allPermissionIds = Permission::pluck('id')->toArray();

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.roles.store'), [
            'name' => 'role_complet',
            'level' => 100,
            'permissions' => $allPermissionIds,
        ]);

    $response->assertRedirect(route('admin.roles.index'));
    $this->assertDatabaseHas('roles', ['name' => 'role_complet', 'level' => 100]);
});

test('admin cannot update role to higher level than own', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('update_roles');

    $existingRole = Role::create(['name' => 'role_existant', 'guard_name' => 'web', 'level' => 30]);

    $response = $this->actingAs($admin)
        ->put(route('admin.roles.update', $existingRole), [
            'name' => 'role_existant',
            'level' => 90,
            'permissions' => [],
        ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('roles', ['id' => $existingRole->id, 'level' => 30]);
});

test('editor cannot update role with permissions they dont have', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $editor->givePermissionTo('update_roles');

    $existingRole = Role::create(['name' => 'role_existant2', 'guard_name' => 'web', 'level' => 30]);
    $manageBackupsId = Permission::where('name', 'manage_backups')->value('id');

    $response = $this->actingAs($editor)
        ->put(route('admin.roles.update', $existingRole), [
            'name' => 'role_existant2',
            'level' => 30,
            'permissions' => [$manageBackupsId],
        ]);

    $response->assertForbidden();
});

test('admin can update role with lower level and owned permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo('update_roles');

    $existingRole = Role::create(['name' => 'role_existant3', 'guard_name' => 'web', 'level' => 30]);
    $viewArticlesId = Permission::where('name', 'view_articles')->value('id');

    $response = $this->actingAs($admin)
        ->put(route('admin.roles.update', $existingRole), [
            'name' => 'role_modifie',
            'level' => 40,
            'permissions' => [$viewArticlesId],
        ]);

    $response->assertRedirect(route('admin.roles.index'));
    $this->assertDatabaseHas('roles', ['id' => $existingRole->id, 'name' => 'role_modifie', 'level' => 40]);
});
