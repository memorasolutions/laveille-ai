<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Health\Models\HealthIncident;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('guest redirected from incidents index', function () {
    $this->get(route('admin.health.incidents.index'))->assertRedirect();
});

test('user without permission gets 403', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.health.incidents.index'))
        ->assertForbidden();
});

test('admin can view incidents index', function () {
    $incident = HealthIncident::forceCreate([
        'title' => 'API Down',
        'status' => 'investigating',
        'severity' => 'major',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.health.incidents.index'))
        ->assertOk()
        ->assertSee('API Down');
});

test('admin can view create form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.health.incidents.create'))
        ->assertOk();
});

test('admin can store incident', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.health.incidents.store'), [
            'title' => 'DB Failure',
            'description' => 'DB unreachable',
            'status' => 'investigating',
            'severity' => 'major',
        ])
        ->assertRedirect(route('admin.health.incidents.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('health_incidents', [
        'title' => 'DB Failure',
        'status' => 'investigating',
        'severity' => 'major',
    ]);
});

test('admin can view edit form', function () {
    $incident = HealthIncident::forceCreate([
        'title' => 'Test Incident',
        'status' => 'investigating',
        'severity' => 'minor',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.health.incidents.edit', $incident))
        ->assertOk()
        ->assertSee('Test Incident');
});

test('admin can update incident', function () {
    $incident = HealthIncident::forceCreate([
        'title' => 'Old Title',
        'status' => 'investigating',
        'severity' => 'minor',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.health.incidents.update', $incident), [
            'title' => 'Updated Title',
            'description' => 'Updated desc',
            'status' => 'identified',
            'severity' => 'critical',
        ])
        ->assertRedirect(route('admin.health.incidents.index'));

    $this->assertDatabaseHas('health_incidents', [
        'id' => $incident->id,
        'title' => 'Updated Title',
        'status' => 'identified',
    ]);
});

test('admin update to resolved auto-sets resolved_at', function () {
    $incident = HealthIncident::forceCreate([
        'title' => 'Resolving',
        'status' => 'investigating',
        'severity' => 'major',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.health.incidents.update', $incident), [
            'title' => 'Resolving',
            'status' => 'resolved',
            'severity' => 'major',
        ])
        ->assertRedirect();

    $incident->refresh();
    expect($incident->status)->toBe('resolved')
        ->and($incident->resolved_at)->not->toBeNull();
});

test('admin can delete incident', function () {
    $incident = HealthIncident::forceCreate([
        'title' => 'To Delete',
        'status' => 'resolved',
        'severity' => 'minor',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.health.incidents.destroy', $incident))
        ->assertRedirect(route('admin.health.incidents.index'));

    $this->assertDatabaseMissing('health_incidents', ['id' => $incident->id]);
});

test('store validates required title', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.health.incidents.store'), [
            'status' => 'investigating',
            'severity' => 'major',
        ])
        ->assertSessionHasErrors('title');
});
