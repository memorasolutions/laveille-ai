<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Plan;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('plan model has correct fillable attributes', function () {
    $plan = new Plan;

    expect($plan->getFillable())->toContain('name', 'slug', 'price', 'interval', 'is_active');
});

test('plan model casts features to array', function () {
    $plan = Plan::factory()->create(['features' => ['storage' => '10GB', 'users' => 5]]);

    expect($plan->features)->toBeArray();
    expect($plan->features['storage'])->toBe('10GB');
    expect($plan->features['users'])->toBe(5);
});

test('plan model casts is_active to boolean', function () {
    $plan = Plan::factory()->create(['is_active' => 1]);

    expect($plan->is_active)->toBeTrue();
});

test('plan active scope filters correctly', function () {
    Plan::factory()->count(2)->create(['is_active' => true]);
    Plan::factory()->create(['is_active' => false]);

    expect(Plan::active()->count())->toBe(2);
});

test('plan monthly scope filters correctly', function () {
    Plan::factory()->create(['interval' => 'monthly']);
    Plan::factory()->create(['interval' => 'yearly']);

    expect(Plan::monthly()->count())->toBe(1);
});

test('plan yearly scope filters correctly', function () {
    Plan::factory()->create(['interval' => 'yearly']);
    Plan::factory()->create(['interval' => 'monthly']);

    expect(Plan::yearly()->count())->toBe(1);
});

test('plan ordered scope sorts by sort_order then price', function () {
    Plan::factory()->create(['sort_order' => 2, 'price' => 10]);
    Plan::factory()->create(['sort_order' => 1, 'price' => 20]);
    Plan::factory()->create(['sort_order' => 1, 'price' => 5]);

    $plans = Plan::ordered()->get();

    expect($plans->first()->price)->toBe('5.00');
    expect($plans->last()->sort_order)->toBe(2);
});

test('plan factory creates valid plan', function () {
    $plan = Plan::factory()->create();

    expect($plan->name)->toBeString();
    expect($plan->slug)->toBeString();
    expect($plan->interval)->toBeIn(['monthly', 'yearly']);
});

test('plan factory inactive state works', function () {
    $plan = Plan::factory()->inactive()->create();

    expect($plan->is_active)->toBeFalse();
});

test('plan factory free state works', function () {
    $plan = Plan::factory()->free()->create();

    expect($plan->price)->toBe('0.00');
});
