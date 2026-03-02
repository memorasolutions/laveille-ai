<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app(TenantService::class)->clear();

    Route::get('/test-domain', fn () => response()->json([
        'tenant_id' => app(TenantService::class)->getCurrent()?->id,
    ]))->middleware(['web', 'tenant.domain']);
});

afterEach(function () {
    app(TenantService::class)->clear();
});

test('resolves tenant by exact domain', function () {
    $tenant = Tenant::factory()->create(['domain' => 'app.acme.com']);

    $this->get('http://app.acme.com/test-domain')
        ->assertOk()
        ->assertJson(['tenant_id' => $tenant->id]);
});

test('resolves tenant by subdomain', function () {
    $tenant = Tenant::factory()->create(['slug' => 'acme']);

    $this->get('http://acme.example.com/test-domain')
        ->assertOk()
        ->assertJson(['tenant_id' => $tenant->id]);
});

test('does not resolve inactive tenant domain', function () {
    Tenant::factory()->inactive()->create(['domain' => 'inactive.acme.com']);

    $this->get('http://inactive.acme.com/test-domain')
        ->assertOk()
        ->assertJson(['tenant_id' => null]);
});

test('returns null when no match', function () {
    $this->get('http://unknown.random.com/test-domain')
        ->assertOk()
        ->assertJson(['tenant_id' => null]);
});

test('does not resolve two part host as subdomain', function () {
    Tenant::factory()->create(['slug' => 'example']);

    $this->get('http://example.com/test-domain')
        ->assertOk()
        ->assertJson(['tenant_id' => null]);
});
