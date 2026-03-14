<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Health\Models\HealthIncident;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('has a status route that resolves correctly', function () {
    expect(route('status.index'))->toEndWith('/status');
});

it('shows incidents on the status page data', function () {
    HealthIncident::forceCreate([
        'title' => 'Test Incident',
        'description' => 'Test description',
        'status' => 'investigating',
        'severity' => 'minor',
    ]);

    $incidents = HealthIncident::recent()->get();

    expect($incidents)->toHaveCount(1)
        ->and($incidents->first()->title)->toBe('Test Incident');
});

it('filters recent incidents correctly', function () {
    HealthIncident::forceCreate([
        'title' => 'Recent',
        'status' => 'investigating',
        'severity' => 'minor',
        'created_at' => now()->subDays(30),
    ]);

    HealthIncident::forceCreate([
        'title' => 'Old',
        'status' => 'resolved',
        'severity' => 'minor',
        'created_at' => now()->subDays(100),
    ]);

    $recent = HealthIncident::recent()->get();

    expect($recent)->toHaveCount(1)
        ->and($recent->first()->title)->toBe('Recent');
});

it('shows no incidents in empty state', function () {
    $incidents = HealthIncident::recent()->get();
    expect($incidents)->toHaveCount(0);
});
