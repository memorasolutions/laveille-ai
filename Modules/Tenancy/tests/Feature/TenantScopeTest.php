<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;
use Modules\Tenancy\Traits\BelongsToTenant;

class TenantTestModel extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_test_models';

    protected $fillable = ['name', 'tenant_id'];
}

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Schema::create('tenant_test_models', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->nullable();
        $table->string('name');
        $table->timestamps();
    });

    // Ensure clean tenant state
    app(TenantService::class)->clear();
});

afterEach(function () {
    app(TenantService::class)->clear();
    Schema::dropIfExists('tenant_test_models');
});

test('global scope filters by current tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    TenantTestModel::create(['name' => 'Tenant 1 Item', 'tenant_id' => $tenant1->id]);
    TenantTestModel::create(['name' => 'Tenant 2 Item', 'tenant_id' => $tenant2->id]);

    app(TenantService::class)->switchTo($tenant1);

    expect(TenantTestModel::count())->toBe(1)
        ->and(TenantTestModel::first()->name)->toBe('Tenant 1 Item');
});

test('global scope does not filter when no tenant set', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    TenantTestModel::create(['name' => 'Item 1', 'tenant_id' => $tenant1->id]);
    TenantTestModel::create(['name' => 'Item 2', 'tenant_id' => $tenant2->id]);

    app(TenantService::class)->clear();

    expect(TenantTestModel::count())->toBe(2);
});

test('auto-sets tenant_id on creating when tenant is set', function () {
    $tenant = Tenant::factory()->create();
    app(TenantService::class)->switchTo($tenant);

    $model = TenantTestModel::create(['name' => 'Auto Set']);

    expect($model->tenant_id)->toBe($tenant->id);
});

test('does not auto-set tenant_id when already provided', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    app(TenantService::class)->switchTo($tenant1);

    $model = TenantTestModel::create([
        'name' => 'Explicit Set',
        'tenant_id' => $tenant2->id,
    ]);

    expect($model->tenant_id)->toBe($tenant2->id);
});

test('does not auto-set tenant_id when no tenant set', function () {
    app(TenantService::class)->clear();

    $model = TenantTestModel::create(['name' => 'No Tenant']);

    expect($model->tenant_id)->toBeNull();
});

test('scopeWithoutTenancy removes the global scope', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    TenantTestModel::create(['name' => 'Item 1', 'tenant_id' => $tenant1->id]);
    TenantTestModel::create(['name' => 'Item 2', 'tenant_id' => $tenant2->id]);

    app(TenantService::class)->switchTo($tenant1);

    expect(TenantTestModel::count())->toBe(1);
    expect(TenantTestModel::withoutTenancy()->count())->toBe(2);
});

test('scopeForTenant filters for specific tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    TenantTestModel::create(['name' => 'Item 1', 'tenant_id' => $tenant1->id]);
    TenantTestModel::create(['name' => 'Item 2', 'tenant_id' => $tenant2->id]);
    TenantTestModel::create(['name' => 'Item 3', 'tenant_id' => $tenant2->id]);

    app(TenantService::class)->clear();

    expect(TenantTestModel::forTenant($tenant1)->count())->toBe(1)
        ->and(TenantTestModel::forTenant($tenant2)->count())->toBe(2);
});

test('tenant relation returns correct tenant', function () {
    $tenant = Tenant::factory()->create();
    $model = TenantTestModel::create(['name' => 'Rel Test', 'tenant_id' => $tenant->id]);

    expect($model->tenant)->toBeInstanceOf(Tenant::class)
        ->and($model->tenant->id)->toBe($tenant->id);
});

test('multiple tenants are properly isolated', function () {
    $service = app(TenantService::class);

    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $service->switchTo($tenantA);
    TenantTestModel::create(['name' => 'A Data']);

    $service->switchTo($tenantB);
    TenantTestModel::create(['name' => 'B Data']);

    $service->switchTo($tenantA);
    $modelsA = TenantTestModel::all();
    expect($modelsA)->toHaveCount(1)
        ->and($modelsA->first()->name)->toBe('A Data');

    $service->switchTo($tenantB);
    $modelsB = TenantTestModel::all();
    expect($modelsB)->toHaveCount(1)
        ->and($modelsB->first()->name)->toBe('B Data');
});

test('model can be created without tenant', function () {
    app(TenantService::class)->clear();

    $model = TenantTestModel::create(['name' => 'Global Resource']);

    expect($model->exists)->toBeTrue()
        ->and($model->tenant_id)->toBeNull();
});
