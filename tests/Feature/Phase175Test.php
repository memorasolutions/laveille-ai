<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('stats page accessible by admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stats'))
        ->assertOk();
});

test('stats page requires authentication', function () {
    $this->get(route('admin.stats'))
        ->assertRedirect();
});

test('stats page shows stats cards', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stats'))
        ->assertOk()
        ->assertSee('Total utilisateurs', false);
});

test('stats page shows chart containers', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stats'))
        ->assertOk()
        ->assertSee('chart-user-growth', false)
        ->assertSee('chart-activity', false)
        ->assertSee('chart-content', false)
        ->assertSee('chart-webhooks', false)
        ->assertSee('chart-categories', false);
});

test('stats page shows period selector', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stats'))
        ->assertOk()
        ->assertSee('7 jours', false)
        ->assertSee('30 jours', false)
        ->assertSee('90 jours', false);
});

test('stats page accepts days parameter', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stats', ['days' => 7]))
        ->assertOk()
        ->assertSee('btn-primary', false);
});

test('stats page uses WowDash pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.stats'))
        ->assertOk()
        ->assertSee('card', false);
});

test('analytics overview endpoint returns json', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.analytics.overview'))
        ->assertOk()
        ->assertJsonStructure(['data' => ['total_users']]);
});

test('analytics content endpoint returns json', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.analytics.content'))
        ->assertOk()
        ->assertJsonStructure(['data' => ['articles_created']]);
});

test('analytics activity endpoint returns json', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.analytics.activity'))
        ->assertOk()
        ->assertJsonStructure(['success', 'data']);
});
