<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('superadmin user cannot be deleted via model', function () {
    $superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $superadmin->assignRole('super_admin');

    $superadmin->delete();

    expect(User::where('email', config('app.superadmin_email'))->exists())->toBeTrue();
});

test('superadmin user cannot be deleted via policy', function () {
    $superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $superadmin->assignRole('super_admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($admin->can('delete', $superadmin))->toBeFalse();
});

test('superadmin user cannot be updated by non-superadmin via policy', function () {
    $superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $superadmin->assignRole('super_admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($admin->can('update', $superadmin))->toBeFalse();
});

test('superadmin can update themselves via policy', function () {
    $superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $superadmin->assignRole('super_admin');

    expect($superadmin->can('update', $superadmin))->toBeTrue();
});

test('isSuperAdmin returns true for matching email and role', function () {
    $superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $superadmin->assignRole('super_admin');

    expect($superadmin->isSuperAdmin())->toBeTrue();
});

test('isSuperAdmin returns false for wrong email', function () {
    $user = User::factory()->create(['email' => 'autre@example.com']);
    $user->assignRole('super_admin');

    expect($user->isSuperAdmin())->toBeFalse();
});

test('isSuperAdmin returns false for correct email but wrong role', function () {
    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('admin');

    expect($user->isSuperAdmin())->toBeFalse();
});

test('regular user can be deleted via model', function () {
    $user = User::factory()->create(['email' => 'regular@example.com']);

    $user->delete();

    expect(User::where('email', 'regular@example.com')->exists())->toBeFalse();
});

test('admin can delete regular user via policy', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $regular = User::factory()->create();

    expect($admin->can('delete', $regular))->toBeTrue();
});
