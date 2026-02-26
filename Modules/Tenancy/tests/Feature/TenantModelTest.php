<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Models\Tenant;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('tenant model has correct fillable attributes', function () {
    $tenant = new Tenant;

    expect($tenant->getFillable())->toContain('name', 'slug', 'domain', 'owner_id', 'settings', 'is_active');
});

test('tenant model casts settings to array', function () {
    $tenant = Tenant::factory()->create(['settings' => ['timezone' => 'UTC', 'locale' => 'en']]);

    expect($tenant->settings)->toBeArray();
    expect($tenant->settings['timezone'])->toBe('UTC');
});

test('tenant model casts is_active to boolean', function () {
    $tenant = Tenant::factory()->create(['is_active' => 1]);

    expect($tenant->is_active)->toBeTrue();
});

test('tenant belongs to owner', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_id' => $user->id]);

    expect($tenant->owner)->toBeInstanceOf(User::class);
    expect($tenant->owner->id)->toBe($user->id);
});

test('tenant active scope filters correctly', function () {
    Tenant::factory()->count(2)->create(['is_active' => true]);
    Tenant::factory()->create(['is_active' => false]);

    expect(Tenant::active()->count())->toBe(2);
});

test('tenant can get setting with default', function () {
    $tenant = Tenant::factory()->create(['settings' => ['theme' => 'dark']]);

    expect($tenant->getSetting('theme'))->toBe('dark');
    expect($tenant->getSetting('missing', 'default'))->toBe('default');
});

test('tenant can set setting', function () {
    $tenant = Tenant::factory()->create(['settings' => []]);

    $tenant->setSetting('language', 'fr');

    expect($tenant->fresh()->getSetting('language'))->toBe('fr');
});

test('tenant factory creates valid tenant', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->name)->toBeString();
    expect($tenant->slug)->toBeString();
    expect($tenant->is_active)->toBeBool();
});

test('tenant factory inactive state works', function () {
    $tenant = Tenant::factory()->inactive()->create();

    expect($tenant->is_active)->toBeFalse();
});

test('tenant factory withOwner state works', function () {
    $tenant = Tenant::factory()->withOwner()->create();

    expect($tenant->owner_id)->not->toBeNull();
    expect($tenant->owner)->toBeInstanceOf(User::class);
});
