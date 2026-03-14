<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Backoffice\Models\FeatureFlagCondition;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('feature flags page loads', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.feature-flags.index'));
    $response->assertOk();
});

test('page shows conditions column', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.feature-flags.index'));
    $response->assertSee('Conditions');
});

test('conditions route is registered', function () {
    expect(\Illuminate\Support\Facades\Route::has('admin.feature-flags.conditions'))->toBeTrue();
});

test('can save always condition', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.feature-flags.conditions', 'test-feature'), [
        'condition_type' => 'always',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('feature_flag_conditions', [
        'feature_name' => 'test-feature',
        'condition_type' => 'always',
    ]);
});

test('can save percentage condition', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.feature-flags.conditions', 'test-feature'), [
        'condition_type' => 'percentage',
        'condition_config' => ['percentage' => 50],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('feature_flag_conditions', [
        'feature_name' => 'test-feature',
        'condition_type' => 'percentage',
    ]);
});

test('can save roles condition', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.feature-flags.conditions', 'test-feature'), [
        'condition_type' => 'roles',
        'condition_config' => ['roles' => ['super_admin']],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('feature_flag_conditions', [
        'feature_name' => 'test-feature',
        'condition_type' => 'roles',
    ]);
});

test('can save environment condition', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.feature-flags.conditions', 'test-feature'), [
        'condition_type' => 'environment',
        'condition_config' => ['environments' => ['local']],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('feature_flag_conditions', [
        'feature_name' => 'test-feature',
        'condition_type' => 'environment',
    ]);
});

test('can save schedule condition', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.feature-flags.conditions', 'test-feature'), [
        'condition_type' => 'schedule',
        'condition_config' => [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('feature_flag_conditions', [
        'feature_name' => 'test-feature',
        'condition_type' => 'schedule',
    ]);
});

test('validates condition_type required', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.feature-flags.conditions', 'test-feature'), [
        'condition_config' => [],
    ]);

    $response->assertSessionHasErrors('condition_type');
});

test('validates condition_type in allowed values', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.feature-flags.conditions', 'test-feature'), [
        'condition_type' => 'invalid',
    ]);

    $response->assertSessionHasErrors('condition_type');
});

test('model evaluates always condition', function () {
    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'always',
    ]);

    expect($condition->isActive())->toBeTrue();
});

test('model evaluates percentage 100', function () {
    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'percentage',
        'condition_config' => ['percentage' => 100],
    ]);

    expect($condition->isActive())->toBeTrue();
});

test('model evaluates percentage 0', function () {
    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'percentage',
        'condition_config' => ['percentage' => 0],
    ]);

    expect($condition->isActive())->toBeFalse();
});

test('model evaluates roles condition for matching user', function () {
    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'roles',
        'condition_config' => ['roles' => ['super_admin']],
    ]);

    expect($condition->isActive($this->admin))->toBeTrue();
});

test('model evaluates roles condition for non-matching user', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'roles',
        'condition_config' => ['roles' => ['super_admin']],
    ]);

    expect($condition->isActive($user))->toBeFalse();
});

test('model evaluates environment condition matching', function () {
    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'environment',
        'condition_config' => ['environments' => ['testing']],
    ]);

    expect($condition->isActive())->toBeTrue();
});

test('model evaluates schedule condition active', function () {
    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'schedule',
        'condition_config' => [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ],
    ]);

    expect($condition->isActive())->toBeTrue();
});

test('model evaluates schedule condition expired', function () {
    $condition = FeatureFlagCondition::create([
        'feature_name' => 'test',
        'condition_type' => 'schedule',
        'condition_config' => [
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
        ],
    ]);

    expect($condition->isActive())->toBeFalse();
});
