<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
});

test('guest is redirected to login on storage index', function () {
    $this->get(route('admin.storage.index'))
        ->assertRedirect(route('login'));
});

test('user without permission gets 403 on storage index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.storage.index'))
        ->assertForbidden();
});

test('user with permission can access storage index', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_storage');

    $this->actingAs($user)
        ->get(route('admin.storage.index'))
        ->assertOk()
        ->assertViewIs('storage::admin.index');
});

test('storage index shows disk names', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_storage');

    $this->actingAs($user)
        ->get(route('admin.storage.index'))
        ->assertOk()
        ->assertViewHas('disks');
});

test('guest is redirected to login on storage show', function () {
    $this->get(route('admin.storage.show', 'public'))
        ->assertRedirect(route('login'));
});

test('user without permission gets 403 on storage show', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.storage.show', 'public'))
        ->assertForbidden();
});

test('user with permission can access storage show for valid disk', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_storage');

    $this->actingAs($user)
        ->get(route('admin.storage.show', 'public'))
        ->assertOk()
        ->assertViewIs('storage::admin.show')
        ->assertViewHas('disk', 'public');
});

test('storage show returns 404 for invalid disk', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_storage');

    $this->actingAs($user)
        ->get(route('admin.storage.show', 'nonexistent-disk'))
        ->assertNotFound();
});
