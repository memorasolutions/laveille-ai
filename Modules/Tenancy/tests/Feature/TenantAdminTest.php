<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Tenancy\Models\Tenant;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function tenantAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

test('guest is redirected to login', function () {
    $this->get(route('admin.tenants.index'))
        ->assertRedirect(route('login'));
});

test('user without permission gets 403', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.tenants.index'))
        ->assertForbidden();
});

test('admin can view index', function () {
    $tenants = Tenant::factory()->count(3)->create();

    $this->actingAs(tenantAdminUser())
        ->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertSee($tenants->first()->name);
});

test('admin can view create form', function () {
    $this->actingAs(tenantAdminUser())
        ->get(route('admin.tenants.create'))
        ->assertOk();
});

test('admin can store tenant', function () {
    $data = [
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
        'domain' => 'acme.localhost',
    ];

    $this->actingAs(tenantAdminUser())
        ->post(route('admin.tenants.store'), $data)
        ->assertRedirect(route('admin.tenants.index'));

    $this->assertDatabaseHas('tenants', [
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
    ]);
});

test('admin can view show', function () {
    $tenant = Tenant::factory()->create();

    $this->actingAs(tenantAdminUser())
        ->get(route('admin.tenants.show', $tenant))
        ->assertOk()
        ->assertSee($tenant->name);
});

test('admin can view edit form', function () {
    $tenant = Tenant::factory()->create();

    $this->actingAs(tenantAdminUser())
        ->get(route('admin.tenants.edit', $tenant))
        ->assertOk();
});

test('admin can update tenant', function () {
    $tenant = Tenant::factory()->create();
    $data = [
        'name' => 'Updated Corp',
        'slug' => 'updated-corp',
        'domain' => 'updated.localhost',
    ];

    $this->actingAs(tenantAdminUser())
        ->put(route('admin.tenants.update', $tenant), $data)
        ->assertRedirect(route('admin.tenants.index'));

    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'name' => 'Updated Corp',
    ]);
});

test('admin can delete tenant', function () {
    $tenant = Tenant::factory()->create();

    $this->actingAs(tenantAdminUser())
        ->delete(route('admin.tenants.destroy', $tenant))
        ->assertRedirect(route('admin.tenants.index'));

    $this->assertDatabaseMissing('tenants', [
        'id' => $tenant->id,
    ]);
});

test('store validates required fields', function () {
    $this->actingAs(tenantAdminUser())
        ->post(route('admin.tenants.store'), [])
        ->assertSessionHasErrors(['name', 'slug']);
});
