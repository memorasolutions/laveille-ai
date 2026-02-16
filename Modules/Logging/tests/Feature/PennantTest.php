<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('pennant feature flags are available', function () {
    expect(class_exists(Feature::class))->toBeTrue();
});

test('feature flag can be activated and deactivated', function () {
    Feature::define('module-saas', false);

    expect(Feature::active('module-saas'))->toBeFalse();

    Feature::activate('module-saas');

    expect(Feature::active('module-saas'))->toBeTrue();

    Feature::deactivate('module-saas');

    expect(Feature::active('module-saas'))->toBeFalse();
});

test('feature flag can be scoped to a user', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    Feature::define('beta-feature', false);

    Feature::for($admin)->activate('beta-feature');

    expect(Feature::for($admin)->active('beta-feature'))->toBeTrue();
    expect(Feature::for($user)->active('beta-feature'))->toBeFalse();
});

test('module feature flags are defined', function () {
    $defined = Feature::defined();

    expect($defined)->toContain('module-saas');
    expect($defined)->toContain('module-tenancy');
});
