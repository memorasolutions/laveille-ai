<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Modules\SaaS\Models\Plan;
use Modules\SaaS\Services\BillingService;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;

uses(RefreshDatabase::class);

// --- SaaS Module Tests ---

test('billing service is registered as singleton', function () {
    $service1 = app(BillingService::class);
    $service2 = app(BillingService::class);

    expect($service1)->toBeInstanceOf(BillingService::class);
    expect($service1)->toBe($service2);
});

test('billing service reports feature flag status', function () {
    $service = app(BillingService::class);

    expect($service->isEnabled())->toBeFalse();

    Feature::activate('module-saas');
    expect($service->isEnabled())->toBeTrue();
});

test('plan model can be created with factory', function () {
    $plan = Plan::factory()->create();

    expect($plan)->toBeInstanceOf(Plan::class);
    expect($plan->name)->toBeString();
    expect($plan->is_active)->toBeTrue();
});

test('plan model has active scope', function () {
    Plan::factory()->count(3)->create();
    Plan::factory()->inactive()->count(2)->create();

    expect(Plan::active()->count())->toBe(3);
});

test('plan model has interval scopes', function () {
    Plan::factory()->monthly()->count(2)->create();
    Plan::factory()->yearly()->count(3)->create();

    expect(Plan::monthly()->count())->toBe(2);
    expect(Plan::yearly()->count())->toBe(3);
});

test('plan model casts features to array', function () {
    $plan = Plan::factory()->create(['features' => ['api', 'export', 'support']]);

    expect($plan->features)->toBeArray();
    expect($plan->features)->toContain('api');
});

test('billing service can create and retrieve plans', function () {
    $service = app(BillingService::class);

    $plan = $service->createPlan([
        'name' => 'Pro',
        'slug' => 'pro',
        'price' => 29.99,
        'currency' => 'cad',
        'interval' => 'monthly',
        'features' => ['unlimited_users', 'api_access'],
        'is_active' => true,
    ]);

    expect($plan->id)->not->toBeNull();
    expect($service->findPlanBySlug('pro'))->not->toBeNull();
    expect($service->getActivePlans())->toHaveCount(1);
});

test('billing service can update and delete plans', function () {
    $service = app(BillingService::class);
    $plan = Plan::factory()->create(['name' => 'Basic']);

    $updated = $service->updatePlan($plan, ['name' => 'Starter']);
    expect($updated->name)->toBe('Starter');

    $deleted = $service->deletePlan($plan->fresh());
    expect($deleted)->toBeTrue();
    expect($service->getPlansCount())->toBe(0);
});

test('billing service can count plans', function () {
    $service = app(BillingService::class);
    Plan::factory()->count(5)->create();
    Plan::factory()->inactive()->count(2)->create();

    expect($service->getPlansCount())->toBe(7);
    expect($service->getActivePlansCount())->toBe(5);
});

// --- Tenancy Module Tests ---

test('tenant service is registered as singleton', function () {
    $service1 = app(TenantService::class);
    $service2 = app(TenantService::class);

    expect($service1)->toBeInstanceOf(TenantService::class);
    expect($service1)->toBe($service2);
});

test('tenant service reports feature flag status', function () {
    $service = app(TenantService::class);

    expect($service->isEnabled())->toBeFalse();

    Feature::activate('module-tenancy');
    expect($service->isEnabled())->toBeTrue();
});

test('tenant model can be created with factory', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->name)->toBeString();
    expect($tenant->is_active)->toBeTrue();
});

test('tenant model has active scope', function () {
    Tenant::factory()->count(3)->create();
    Tenant::factory()->inactive()->count(2)->create();

    expect(Tenant::active()->count())->toBe(3);
});

test('tenant model belongs to owner', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->withOwner($user)->create();

    expect($tenant->owner->id)->toBe($user->id);
});

test('tenant model can manage settings', function () {
    $tenant = Tenant::factory()->create(['settings' => ['theme' => 'dark']]);

    expect($tenant->getSetting('theme'))->toBe('dark');
    expect($tenant->getSetting('nonexistent', 'default'))->toBe('default');

    $tenant->setSetting('locale', 'fr');
    expect($tenant->fresh()->getSetting('locale'))->toBe('fr');
});

test('tenant service can create and find tenants', function () {
    $service = app(TenantService::class);

    $tenant = $service->create([
        'name' => 'Acme Corp',
        'slug' => 'acme',
        'domain' => 'acme.example.com',
        'is_active' => true,
    ]);

    expect($tenant->id)->not->toBeNull();
    expect($service->findBySlug('acme'))->not->toBeNull();
    expect($service->findByDomain('acme.example.com'))->not->toBeNull();
});

test('tenant service can switch current tenant', function () {
    $service = app(TenantService::class);
    $tenant = Tenant::factory()->create();

    expect($service->getCurrent())->toBeNull();

    $service->switchTo($tenant);
    expect($service->getCurrent()->id)->toBe($tenant->id);

    $service->clear();
    expect($service->getCurrent())->toBeNull();
});

test('tenant service can get tenants for user', function () {
    $service = app(TenantService::class);
    $user = User::factory()->create();
    Tenant::factory()->withOwner($user)->count(3)->create();
    Tenant::factory()->create();

    expect($service->getForUser($user))->toHaveCount(3);
});

test('tenant service can update and delete tenants', function () {
    $service = app(TenantService::class);
    $tenant = Tenant::factory()->create(['name' => 'Old Name']);

    $updated = $service->update($tenant, ['name' => 'New Name']);
    expect($updated->name)->toBe('New Name');

    $deleted = $service->delete($tenant->fresh());
    expect($deleted)->toBeTrue();
    expect($service->getCount())->toBe(0);
});

// --- Feature Flag Integration Tests ---

test('saas feature flag defaults to false', function () {
    expect(Feature::active('module-saas'))->toBeFalse();
});

test('tenancy feature flag defaults to false', function () {
    expect(Feature::active('module-tenancy'))->toBeFalse();
});

test('feature flags can be toggled independently', function () {
    Feature::activate('module-saas');
    expect(Feature::active('module-saas'))->toBeTrue();
    expect(Feature::active('module-tenancy'))->toBeFalse();

    Feature::activate('module-tenancy');
    expect(Feature::active('module-tenancy'))->toBeTrue();

    Feature::deactivate('module-saas');
    expect(Feature::active('module-saas'))->toBeFalse();
    expect(Feature::active('module-tenancy'))->toBeTrue();
});

// --- Package Integration Tests ---

test('laravel cashier package is installed', function () {
    expect(class_exists(\Laravel\Cashier\Cashier::class))->toBeTrue();
});

test('stancl tenancy package is installed', function () {
    expect(class_exists(\Stancl\Tenancy\Tenancy::class))->toBeTrue();
});
