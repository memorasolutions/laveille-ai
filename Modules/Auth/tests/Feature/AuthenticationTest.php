<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
});

test('login page is accessible', function () {
    $this->get('/login')->assertOk();
});

test('admin user can access admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('non-admin user is forbidden from admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('guest is redirected to login', function () {
    $this->get('/admin')
        ->assertRedirect('/login');
});

test('user model has HasRoles trait', function () {
    $user = User::factory()->create();

    expect(method_exists($user, 'assignRole'))->toBeTrue();
    expect(method_exists($user, 'hasRole'))->toBeTrue();
});

test('user model has HasApiTokens trait', function () {
    $user = User::factory()->create();

    expect(method_exists($user, 'createToken'))->toBeTrue();
});

test('user model has LogsActivity trait', function () {
    $user = User::factory()->create();

    expect(method_exists($user, 'getActivitylogOptions'))->toBeTrue();
});
