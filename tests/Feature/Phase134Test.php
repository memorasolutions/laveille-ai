<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

test('plugin list accessible by super_admin', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(route('admin.plugins.index'))
        ->assertOk();
});

test('plugin list forbidden for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.plugins.index'))
        ->assertForbidden();
});

test('plugin list redirects unauthenticated', function () {
    $this->get(route('admin.plugins.index'))
        ->assertRedirect();
});

test('plugin list shows module names', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(route('admin.plugins.index'))
        ->assertOk()
        ->assertSee('Blog')
        ->assertSee('Core');
});

test('toggle protected module returns error', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->post(route('admin.plugins.toggle', 'Core'))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('toggle non-protected module works', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->post(route('admin.plugins.toggle', 'Webhooks'))
        ->assertRedirect()
        ->assertSessionHas('success');

    // Re-enable to avoid side effects on other tests
    \Nwidart\Modules\Facades\Module::enable('Webhooks');
});

test('route admin.plugins.index exists', function () {
    expect(route('admin.plugins.index'))->not->toBeNull();
});

test('route admin.plugins.toggle exists', function () {
    expect(route('admin.plugins.toggle', 'Blog'))->not->toBeNull();
});
