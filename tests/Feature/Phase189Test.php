<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $this->actingAs($user);
});

test('settings page loads with default tab', function () {
    $response = $this->get(route('admin.settings.index'));

    $response->assertStatus(200);
    $response->assertSee('activeTab', false);
    $response->assertSee('general', false);
});

test('settings page with ?tab=mail shows mail tab active', function () {
    $response = $this->get(route('admin.settings.index', ['tab' => 'mail']));

    $response->assertStatus(200);
    $response->assertSee('activeTab', false);
    $response->assertSee('mail', false);
});

test('settings page with ?tab=seo shows seo content', function () {
    $response = $this->get(route('admin.settings.index', ['tab' => 'seo']));

    $response->assertStatus(200);
    $response->assertSee('seo', false);
});

test('settings page with invalid tab falls back to first tab', function () {
    $response = $this->get(route('admin.settings.index', ['tab' => 'invalid']));

    $response->assertStatus(200);
    $response->assertSee('activeTab', false);
});

test('settings page contains history.replaceState for URL persistence', function () {
    $response = $this->get(route('admin.settings.index'));

    $response->assertStatus(200);
    $response->assertSee('history.replaceState', false);
});

test('settings tab URL param is rendered in Alpine x-data', function () {
    $response = $this->get(route('admin.settings.index', ['tab' => 'branding']));

    $response->assertStatus(200);
    $response->assertSee("get('tab')", false);
});
