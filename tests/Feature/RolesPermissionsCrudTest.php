<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));

    $this->user = User::factory()->create();
});

// --- Authentification ---

it('redirige un visiteur pour la liste des rôles', function () {
    $this->get('/admin/roles')->assertRedirect();
});

it('redirige un visiteur pour la création de rôle', function () {
    $this->post('/admin/roles', ['name' => 'test'])->assertRedirect();
});

// --- Permissions ---

it('interdit la liste des rôles à un utilisateur sans permission', function () {
    $this->actingAs($this->user)->get('/admin/roles')->assertForbidden();
});

it('interdit la création de rôle à un utilisateur sans permission', function () {
    $this->actingAs($this->user)->post('/admin/roles', ['name' => 'test'])->assertForbidden();
});

// --- Pages chargent correctement ---

it('affiche la liste des rôles pour un super admin', function () {
    $this->actingAs($this->admin)->get('/admin/roles')->assertOk();
});

it('affiche le formulaire de création de rôle', function () {
    $this->actingAs($this->admin)->get('/admin/roles/create')->assertOk();
});

it('affiche les détails d un rôle', function () {
    $role = Role::create(['name' => 'test_role', 'guard_name' => 'web']);

    $this->actingAs($this->admin)->get("/admin/roles/{$role->id}")->assertOk();
});

it('affiche le formulaire d édition d un rôle', function () {
    $role = Role::create(['name' => 'test_role', 'guard_name' => 'web']);

    $this->actingAs($this->admin)->get("/admin/roles/{$role->id}/edit")->assertOk();
});

// --- Création ---

it('permet de créer un rôle avec des permissions', function () {
    $permission = Permission::findByName('view_users', 'web');

    $this->actingAs($this->admin)->post('/admin/roles', [
        'name' => 'nouveau_role',
        'permissions' => [$permission->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('roles', ['name' => 'nouveau_role']);
    expect(Role::findByName('nouveau_role', 'web')->hasPermissionTo('view_users'))->toBeTrue();
});

it('valide que le nom de rôle est requis', function () {
    $this->actingAs($this->admin)
        ->post('/admin/roles', ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('valide que le nom de rôle est unique', function () {
    $this->actingAs($this->admin)
        ->post('/admin/roles', ['name' => 'admin'])
        ->assertSessionHasErrors('name');
});

// --- Mise à jour ---

it('permet de modifier un rôle et synchroniser ses permissions', function () {
    $role = Role::create(['name' => 'test_edit', 'guard_name' => 'web']);
    $permission = Permission::findByName('view_users', 'web');

    $this->actingAs($this->admin)->put("/admin/roles/{$role->id}", [
        'name' => 'test_edit_renamed',
        'permissions' => [$permission->id],
    ])->assertRedirect();

    expect($role->fresh()->name)->toBe('test_edit_renamed')
        ->and($role->fresh()->hasPermissionTo('view_users'))->toBeTrue();
});

it('valide l unicité du nom lors de la modification', function () {
    $role = Role::create(['name' => 'role_a', 'guard_name' => 'web']);

    $this->actingAs($this->admin)
        ->put("/admin/roles/{$role->id}", ['name' => 'admin'])
        ->assertSessionHasErrors('name');
});

// --- Suppression ---

it('permet de supprimer un rôle personnalisé', function () {
    $role = Role::create(['name' => 'role_jetable', 'guard_name' => 'web']);

    $this->actingAs($this->admin)
        ->delete("/admin/roles/{$role->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

it('empêche la suppression des rôles système', function () {
    $role = Role::findByName('admin', 'web');

    $this->actingAs($this->admin)
        ->delete("/admin/roles/{$role->id}");

    $this->assertDatabaseHas('roles', ['name' => 'admin']);
});
