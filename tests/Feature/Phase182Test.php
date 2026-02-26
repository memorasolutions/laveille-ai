<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('sidebar contains system info link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.system-info'), false);
});

test('sidebar contains data retention link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.data-retention'), false);
});

test('sidebar contains notifications link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.notifications.index'), false);
});

test('sidebar contains push notifications link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.push-notifications.index'), false);
});

test('sidebar contains email templates link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.email-templates.index'), false);
});

test('sidebar contains webhooks link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.webhooks.index'), false);
});

test('sidebar contains shortcodes link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.shortcodes.index'), false);
});

test('sidebar contains cookie categories link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.cookie-categories.index'), false);
});

test('sidebar contains onboarding link', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertSee(route('admin.onboarding-steps.index'), false);
});

test('all new sidebar routes are registered', function () {
    $routes = [
        'admin.system-info',
        'admin.data-retention',
        'admin.notifications.index',
        'admin.push-notifications.index',
        'admin.email-templates.index',
        'admin.webhooks.index',
        'admin.shortcodes.index',
        'admin.cookie-categories.index',
        'admin.onboarding-steps.index',
    ];

    foreach ($routes as $routeName) {
        expect(Route::has($routeName))->toBeTrue();
    }
});
