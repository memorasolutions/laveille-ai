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

test('login history page uses WowDash card pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.login-history'))
        ->assertOk()
        ->assertSee('card', false);
});

test('mail log page uses WowDash card pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.mail-log'))
        ->assertOk()
        ->assertSee('card', false);
});

test('scheduler page uses card table pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.scheduler'))
        ->assertOk()
        ->assertSee('table', false)
        ->assertSee('card', false);
});

test('failed jobs page uses card pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.failed-jobs.index'))
        ->assertOk()
        ->assertSee('card', false);
});

test('blocked ips page uses WowDash card pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blocked-ips.index'))
        ->assertOk()
        ->assertSee('card', false);
});

test('security page uses card table pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.security'))
        ->assertOk()
        ->assertSee('table', false)
        ->assertSee('card', false);
});

test('logs page uses WowDash table pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.logs'))
        ->assertOk()
        ->assertSee('table', false);
});

test('notifications index page uses WowDash table pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.notifications.index'))
        ->assertOk()
        ->assertSee('card', false);
});

test('email templates index page uses WowDash card pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.email-templates.index'))
        ->assertOk()
        ->assertSee('card', false);
});

test('cache page uses card pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.cache'))
        ->assertOk()
        ->assertSee('card', false);
});
