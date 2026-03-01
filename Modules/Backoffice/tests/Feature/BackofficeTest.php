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

test('admin dashboard is accessible for admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('admin dashboard redirects guests', function () {
    $this->get('/admin')
        ->assertRedirect();
});

test('admin routes are registered', function () {
    $this->assertTrue(
        collect(app('router')->getRoutes())->contains(fn ($route) => str_starts_with($route->uri(), 'admin'))
    );
});
