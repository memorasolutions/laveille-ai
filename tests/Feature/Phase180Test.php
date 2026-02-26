<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('purge uses retention setting instead of hardcoded 30 days', function () {
    Setting::set('retention.activity_log_days', 90);
    $response = $this->actingAs($this->admin)->delete(route('admin.activity-logs.purge'));
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('90 jours');
});

test('data retention dashboard loads for admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.data-retention'));
    $response->assertOk();
    $response->assertSee('Rétention des données');
    $response->assertSee('Total enregistrements');
    $response->assertSee('Éligibles au nettoyage');
});

test('data retention dashboard shows table stats', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.data-retention'));
    $response->assertOk();
    $response->assertSee('login_attempts');
    $response->assertSee('sent_emails');
    $response->assertSee('activity_log');
    $response->assertSee('blocked_ips');
    $response->assertSee('magic_login_tokens');
});

test('data retention dashboard shows retention days', function () {
    Setting::set('retention.activity_log_days', 180);
    $response = $this->actingAs($this->admin)->get(route('admin.data-retention'));
    $response->assertOk();
    $response->assertSee('180 j');
});

test('non-admin cannot access data retention dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $response = $this->actingAs($user)->get(route('admin.data-retention'));
    $response->assertForbidden();
});

test('guest cannot access data retention dashboard', function () {
    $response = $this->get(route('admin.data-retention'));
    $response->assertRedirect(route('login'));
});

test('cleanup command runs successfully', function () {
    $this->artisan('app:cleanup')
        ->expectsOutputToContain('Nettoyage terminé')
        ->assertSuccessful();
});

test('cleanup command dry-run does not delete data', function () {
    $this->artisan('app:cleanup', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY-RUN]')
        ->expectsOutputToContain('Nettoyage terminé')
        ->assertSuccessful();
});

test('cleanup command reads retention settings', function () {
    Setting::set('retention.login_attempts_days', 30);
    $this->artisan('app:cleanup', ['--dry-run' => true])
        ->expectsOutputToContain('30 jours')
        ->assertSuccessful();
});

test('data retention route is registered', function () {
    $routes = collect(app('router')->getRoutes()->getRoutesByName());
    expect($routes->has('admin.data-retention'))->toBeTrue();
});
