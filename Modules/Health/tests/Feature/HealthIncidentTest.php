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

it('creates an incident', function () {
    $incident = HealthIncident::forceCreate([
        'title' => 'Service Disruption',
        'description' => 'Brief service disruption',
        'status' => 'investigating',
        'severity' => 'major',
    ]);

    expect($incident)->toBeInstanceOf(HealthIncident::class)
        ->and($incident->title)->toBe('Service Disruption')
        ->and($incident->status)->toBe('investigating');
});

it('scopes recent incidents to last 90 days', function () {
    HealthIncident::forceCreate([
        'title' => 'Recent',
        'status' => 'resolved',
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

it('scopes unresolved incidents', function () {
    HealthIncident::forceCreate([
        'title' => 'Unresolved',
        'status' => 'investigating',
        'severity' => 'critical',
    ]);

    HealthIncident::forceCreate([
        'title' => 'Resolved',
        'status' => 'resolved',
        'severity' => 'minor',
        'resolved_at' => now(),
    ]);

    $unresolved = HealthIncident::unresolved()->get();

    expect($unresolved)->toHaveCount(1)
        ->and($unresolved->first()->title)->toBe('Unresolved');
});

it('checks if incident is resolved', function () {
    $unresolved = HealthIncident::forceCreate([
        'title' => 'Open',
        'status' => 'investigating',
        'severity' => 'minor',
    ]);

    $resolved = HealthIncident::forceCreate([
        'title' => 'Closed',
        'status' => 'resolved',
        'severity' => 'minor',
        'resolved_at' => now(),
    ]);

    expect($unresolved->isResolved())->toBeFalse()
        ->and($resolved->isResolved())->toBeTrue();
});
