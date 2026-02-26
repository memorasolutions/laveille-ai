<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\FeatureFlagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

test('pennant feature class exists', function () {
    expect(class_exists(Feature::class))->toBeTrue();
});

test('feature flags are defined in AppServiceProvider', function () {
    $defined = Feature::defined();

    expect($defined)->toContain('module-saas')
        ->toContain('module-tenancy')
        ->toContain('module-translation')
        ->toContain('module-search')
        ->toContain('module-export')
        ->toContain('module-webhooks')
        ->toContain('module-media')
        ->toContain('module-backup')
        ->toContain('module-sms');
});

test('saas and tenancy flags are disabled by default', function () {
    expect(Feature::active('module-saas'))->toBeFalse();
    expect(Feature::active('module-tenancy'))->toBeFalse();
    expect(Feature::active('module-sms'))->toBeFalse();
});

test('active module flags are enabled by default', function () {
    expect(Feature::active('module-translation'))->toBeTrue();
    expect(Feature::active('module-search'))->toBeTrue();
    expect(Feature::active('module-export'))->toBeTrue();
    expect(Feature::active('module-webhooks'))->toBeTrue();
    expect(Feature::active('module-media'))->toBeTrue();
    expect(Feature::active('module-backup'))->toBeTrue();
});

test('feature flag can be toggled', function () {
    expect(Feature::active('module-saas'))->toBeFalse();
    Feature::activate('module-saas');
    expect(Feature::active('module-saas'))->toBeTrue();
    Feature::deactivate('module-saas');
    expect(Feature::active('module-saas'))->toBeFalse();
});

test('feature flag can be scoped to user', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    Feature::for($admin)->activate('module-saas');

    expect(Feature::for($admin)->active('module-saas'))->toBeTrue();
    expect(Feature::for($user)->active('module-saas'))->toBeFalse();
});

test('feature flag seeder exists', function () {
    expect(class_exists(FeatureFlagSeeder::class))->toBeTrue();
});

test('feature flag seeder activates correct flags', function () {
    $seeder = new FeatureFlagSeeder;
    $seeder->run();

    expect(Feature::active('module-translation'))->toBeTrue();
    expect(Feature::active('module-search'))->toBeTrue();
    expect(Feature::active('module-saas'))->toBeFalse();
    expect(Feature::active('module-tenancy'))->toBeFalse();
});

test('features table migration exists', function () {
    expect(file_exists(base_path('database/migrations/2026_02_15_194032_create_features_table.php')))->toBeTrue();
});

test('pennant config uses database store', function () {
    expect(config('pennant.default'))->toBe('database');
});
