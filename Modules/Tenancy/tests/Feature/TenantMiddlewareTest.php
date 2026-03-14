<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Team\Models\Team;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app(TenantService::class)->clear();

    Route::get('/test-identify', fn () => response()->json([
        'tenant_id' => app(TenantService::class)->getCurrent()?->id,
    ]))->middleware(['web', 'identify.tenant']);

    Route::get('/test-access', fn () => response()->json([
        'ok' => true,
    ]))->middleware(['web', 'identify.tenant', 'tenant.access']);
});

afterEach(function () {
    app(TenantService::class)->clear();
});

test('identify_tenant resolves from header X-Tenant-ID', function () {
    $tenant = Tenant::factory()->create(['slug' => 'acme-corp']);

    $this->get('/test-identify', ['X-Tenant-ID' => 'acme-corp'])
        ->assertOk()
        ->assertJson(['tenant_id' => $tenant->id]);
});

test('identify_tenant resolves from session', function () {
    $tenant = Tenant::factory()->create();

    $this->withSession(['tenant_id' => $tenant->id])
        ->get('/test-identify')
        ->assertOk()
        ->assertJson(['tenant_id' => $tenant->id]);
});

test('identify_tenant does not set inactive tenant', function () {
    $tenant = Tenant::factory()->inactive()->create(['slug' => 'inactive-co']);

    $this->get('/test-identify', ['X-Tenant-ID' => 'inactive-co'])
        ->assertOk()
        ->assertJson(['tenant_id' => null]);
});

test('identify_tenant continues without tenant when none found', function () {
    $this->get('/test-identify')
        ->assertOk()
        ->assertJson(['tenant_id' => null]);
});

test('ensure_tenant_access allows super_admin', function () {
    $tenant = Tenant::factory()->create(['slug' => 'test-tenant']);

    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/test-access', ['X-Tenant-ID' => 'test-tenant'])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('ensure_tenant_access allows tenant owner', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['slug' => 'owner-tenant', 'owner_id' => $user->id]);

    $this->actingAs($user)
        ->get('/test-access', ['X-Tenant-ID' => 'owner-tenant'])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('ensure_tenant_access allows team member within tenant', function () {
    $tenant = Tenant::factory()->create(['slug' => 'team-tenant']);
    $user = User::factory()->create();

    $teamOwner = User::factory()->create();
    $team = Team::factory()->create(['tenant_id' => $tenant->id, 'owner_id' => $teamOwner->id]);
    $team->members()->attach($user, ['role' => 'member', 'accepted_at' => now()]);

    $this->actingAs($user)
        ->get('/test-access', ['X-Tenant-ID' => 'team-tenant'])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('ensure_tenant_access returns 403 for unauthorized user', function () {
    $tenant = Tenant::factory()->create(['slug' => 'restricted']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/test-access', ['X-Tenant-ID' => 'restricted'])
        ->assertForbidden();
});
