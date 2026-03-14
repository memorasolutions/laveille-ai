<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\RolesPermissions\Services\RoleService;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->service = app(RoleService::class);
});

test('role service getAllRoles returns roles with permissions', function () {
    $roles = $this->service->getAllRoles();

    expect($roles->count())->toBeGreaterThanOrEqual(3);
    expect($roles->pluck('name')->toArray())->toContain('super_admin');
});

test('role service getAllPermissions returns permissions', function () {
    $permissions = $this->service->getAllPermissions();

    expect($permissions->count())->toBeGreaterThan(0);
});

test('role service createRole creates role without permissions', function () {
    $role = $this->service->createRole('tester');

    expect($role->name)->toBe('tester');
    expect(Role::where('name', 'tester')->exists())->toBeTrue();
});

test('role service createRole creates role with permissions', function () {
    $role = $this->service->createRole('moderator', ['view_users']);

    expect($role->name)->toBe('moderator');
    expect($role->hasPermissionTo('view_users'))->toBeTrue();
});

test('role service updateRole updates name and permissions', function () {
    $role = $this->service->createRole('temp');
    $updated = $this->service->updateRole($role, 'updated', ['view_users', 'view_media']);

    expect($updated->name)->toBe('updated');
    expect($updated->hasPermissionTo('view_users'))->toBeTrue();
    expect($updated->hasPermissionTo('view_media'))->toBeTrue();
});

test('role service deleteRole prevents deleting protected roles', function () {
    $superAdmin = Role::findByName('super_admin');
    $admin = Role::findByName('admin');

    expect($this->service->deleteRole($superAdmin))->toBeFalse();
    expect($this->service->deleteRole($admin))->toBeFalse();
});

test('role service deleteRole allows deleting custom roles', function () {
    $role = $this->service->createRole('temporary');

    expect($this->service->deleteRole($role))->toBeTrue();
    expect(Role::where('name', 'temporary')->exists())->toBeFalse();
});

test('role service assignRole and syncRoles work correctly', function () {
    $user = User::factory()->create();

    $this->service->assignRole($user, 'user');
    expect($user->hasRole('user'))->toBeTrue();

    $this->service->syncRoles($user, ['admin']);
    expect($user->fresh()->hasRole('admin'))->toBeTrue();
    expect($user->fresh()->hasRole('user'))->toBeFalse();

    $this->service->removeRole($user->fresh(), 'admin');
    expect($user->fresh()->hasRole('admin'))->toBeFalse();
});
