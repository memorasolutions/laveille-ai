<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('roles can be created', function () {
    $role = Role::firstOrCreate(['name' => 'test_role']);

    expect($role)->toBeInstanceOf(Role::class);
    expect($role->name)->toBe('test_role');
});

test('permissions can be created', function () {
    $permission = Permission::create(['name' => 'test_permission']);

    expect($permission)->toBeInstanceOf(Permission::class);
    expect($permission->name)->toBe('test_permission');
});

test('role can be assigned to user', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'editor']);

    $user->assignRole('editor');

    expect($user->hasRole('editor'))->toBeTrue();
});

test('permission can be assigned to role', function () {
    $role = Role::firstOrCreate(['name' => 'editor']);
    $permission = Permission::firstOrCreate(['name' => 'edit_posts']);

    $role->givePermissionTo('edit_posts');

    expect($role->hasPermissionTo('edit_posts'))->toBeTrue();
});

test('user inherits permissions from role', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'editor']);
    $permission = Permission::firstOrCreate(['name' => 'edit_posts']);

    $role->givePermissionTo($permission);
    $user->assignRole($role);

    expect($user->hasPermissionTo('edit_posts'))->toBeTrue();
});

test('seeder creates default roles', function () {
    (new RolesAndPermissionsSeeder)->run();

    expect(Role::where('name', 'super_admin')->exists())->toBeTrue();
    expect(Role::where('name', 'admin')->exists())->toBeTrue();
    expect(Role::where('name', 'user')->exists())->toBeTrue();
});

test('super_admin has all permissions', function () {
    (new RolesAndPermissionsSeeder)->run();

    $superAdmin = Role::findByName('super_admin');

    expect($superAdmin->permissions->count())->toBe(Permission::count());
});

test('non-admin user does not have admin role', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    expect($user->hasRole('admin'))->toBeFalse();
    expect($user->hasRole('super_admin'))->toBeFalse();
});

test('admin user has admin role', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
});
