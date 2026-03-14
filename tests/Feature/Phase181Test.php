<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('admin can view system info page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertOk();
});

test('page shows PHP version', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertSee(PHP_VERSION, false);
});

test('page shows Laravel version', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertSee(app()->version(), false);
});

test('page shows environment info', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertSee('Environnement', false);
});

test('page shows modules section', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertSee('Modules actifs', false);
});

test('page shows PHP extensions section', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertSee('Extensions PHP', false);
});

test('page shows disk info', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertSee('Disque', false);
});

test('page shows server info', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system-info'))
        ->assertSee('Serveur', false);
});

test('non-admin gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->get(route('admin.system-info'))
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get(route('admin.system-info'))
        ->assertRedirect(route('login'));
});

test('route is registered', function () {
    expect(Route::has('admin.system-info'))->toBeTrue();
});
