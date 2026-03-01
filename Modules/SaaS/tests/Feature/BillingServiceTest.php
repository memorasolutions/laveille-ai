<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Plan;
use Modules\SaaS\Services\BillingService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('billing service can be resolved from container', function () {
    $service = app(BillingService::class);

    expect($service)->toBeInstanceOf(BillingService::class);
});

test('billing service returns active plans', function () {
    Plan::factory()->count(2)->create(['is_active' => true]);
    Plan::factory()->create(['is_active' => false]);

    $service = app(BillingService::class);
    $plans = $service->getActivePlans();

    expect($plans)->toHaveCount(2);
});

test('billing service returns monthly plans', function () {
    Plan::factory()->create(['is_active' => true, 'interval' => 'monthly']);
    Plan::factory()->create(['is_active' => true, 'interval' => 'yearly']);

    $service = app(BillingService::class);

    expect($service->getMonthlyPlans())->toHaveCount(1);
});

test('billing service returns yearly plans', function () {
    Plan::factory()->create(['is_active' => true, 'interval' => 'yearly']);
    Plan::factory()->create(['is_active' => true, 'interval' => 'monthly']);

    $service = app(BillingService::class);

    expect($service->getYearlyPlans())->toHaveCount(1);
});

test('billing service can create a plan', function () {
    $service = app(BillingService::class);

    $plan = $service->createPlan([
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => 19.99,
        'interval' => 'monthly',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($plan)->toBeInstanceOf(Plan::class);
    expect($plan->name)->toBe('Test Plan');
    expect($plan->slug)->toBe('test-plan');
    expect($plan->price)->toBe('19.99');
});

test('billing service can update a plan', function () {
    $service = app(BillingService::class);
    $plan = Plan::factory()->create(['name' => 'Old Name']);

    $updated = $service->updatePlan($plan, ['name' => 'New Name']);

    expect($updated->name)->toBe('New Name');
});

test('billing service can delete a plan', function () {
    $service = app(BillingService::class);
    $plan = Plan::factory()->create();

    $result = $service->deletePlan($plan);

    expect($result)->toBeTrue();
    expect(Plan::find($plan->id))->toBeNull();
});

test('billing service can find plan by slug', function () {
    $service = app(BillingService::class);
    Plan::factory()->create(['slug' => 'pro-plan']);

    $plan = $service->findPlanBySlug('pro-plan');

    expect($plan)->toBeInstanceOf(Plan::class);
    expect($plan->slug)->toBe('pro-plan');
});

test('billing service can find plan by id', function () {
    $service = app(BillingService::class);
    $created = Plan::factory()->create();

    $found = $service->findPlan($created->id);

    expect($found)->toBeInstanceOf(Plan::class);
    expect($found->id)->toBe($created->id);
});

test('billing service returns correct counts', function () {
    Plan::factory()->count(3)->create(['is_active' => true]);
    Plan::factory()->count(2)->create(['is_active' => false]);

    $service = app(BillingService::class);

    expect($service->getPlansCount())->toBe(5);
    expect($service->getActivePlansCount())->toBe(3);
});
