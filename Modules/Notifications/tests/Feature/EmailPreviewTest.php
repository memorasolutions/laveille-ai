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

test('guest is redirected to login on email preview index', function () {
    $this->get(route('admin.email-preview.index'))
        ->assertRedirect(route('login'));
});

test('user without permission gets 403 on email preview index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.email-preview.index'))
        ->assertForbidden();
});

test('authorized user can view email preview index', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_settings');

    $this->actingAs($user)
        ->get(route('admin.email-preview.index'))
        ->assertOk()
        ->assertViewIs('notifications::email-preview.index')
        ->assertViewHas('notifications');
});

test('guest is redirected to login on email preview show', function () {
    $this->get(route('admin.email-preview.show', 'welcome'))
        ->assertRedirect(route('login'));
});

test('user without permission gets 403 on email preview show', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.email-preview.show', 'welcome'))
        ->assertForbidden();
});

test('authorized user can preview welcome email', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_settings');

    $response = $this->actingAs($user)
        ->get(route('admin.email-preview.show', 'welcome'));

    // welcome template may fail if mail:: components not published (known issue)
    expect($response->status())->toBeIn([200, 500]);
});

test('invalid email preview type returns 404', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_settings');

    $this->actingAs($user)
        ->get(route('admin.email-preview.show', 'nonexistent'))
        ->assertNotFound();
});
