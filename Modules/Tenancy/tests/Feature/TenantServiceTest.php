<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('tenant service can be resolved from container', function () {
    $service = app(TenantService::class);

    expect($service)->toBeInstanceOf(TenantService::class);
});

test('tenant service can create a tenant', function () {
    $service = app(TenantService::class);

    $tenant = $service->create([
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
        'domain' => 'acme.example.com',
        'is_active' => true,
    ]);

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->name)->toBe('Acme Corp');
    expect($tenant->slug)->toBe('acme-corp');
});

test('tenant service can update a tenant', function () {
    $service = app(TenantService::class);
    $tenant = Tenant::factory()->create(['name' => 'Old Name']);

    $updated = $service->update($tenant, ['name' => 'New Name']);

    expect($updated->name)->toBe('New Name');
});

test('tenant service can delete a tenant', function () {
    $service = app(TenantService::class);
    $tenant = Tenant::factory()->create();

    $result = $service->delete($tenant);

    expect($result)->toBeTrue();
    expect(Tenant::find($tenant->id))->toBeNull();
});

test('tenant service can find by slug', function () {
    $service = app(TenantService::class);
    Tenant::factory()->create(['slug' => 'my-tenant']);

    $tenant = $service->findBySlug('my-tenant');

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->slug)->toBe('my-tenant');
});

test('tenant service can find by domain', function () {
    $service = app(TenantService::class);
    Tenant::factory()->create(['domain' => 'test.example.com']);

    $tenant = $service->findByDomain('test.example.com');

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->domain)->toBe('test.example.com');
});

test('tenant service can get tenants for user', function () {
    $service = app(TenantService::class);
    $user = User::factory()->create();

    Tenant::factory()->count(2)->create(['owner_id' => $user->id]);
    Tenant::factory()->create();

    $tenants = $service->getForUser($user);

    expect($tenants)->toHaveCount(2);
});

test('tenant service can switch and clear current tenant', function () {
    $service = app(TenantService::class);
    $tenant = Tenant::factory()->create();

    expect($service->getCurrent())->toBeNull();

    $service->switchTo($tenant);
    expect($service->getCurrent())->toBeInstanceOf(Tenant::class);
    expect($service->getCurrent()->id)->toBe($tenant->id);

    $service->clear();
    expect($service->getCurrent())->toBeNull();
});

test('tenant service returns active tenants only', function () {
    $service = app(TenantService::class);
    Tenant::factory()->count(3)->create(['is_active' => true]);
    Tenant::factory()->count(2)->create(['is_active' => false]);

    expect($service->getActive())->toHaveCount(3);
    expect($service->getAll())->toHaveCount(5);
});

test('tenant service returns correct count', function () {
    $service = app(TenantService::class);
    Tenant::factory()->count(4)->create();

    expect($service->getCount())->toBe(4);
});
