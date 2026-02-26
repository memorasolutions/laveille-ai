<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('admin can view health page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertOk();
});

test('page shows summary cards', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertSee('Total');
    $response->assertSee('OK');
    $response->assertSee('Avertissements');
});

test('page shows french status labels after refresh', function () {
    $this->actingAs($this->admin)->post(route('admin.health.refresh'));
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertSee('OK');
});

test('page shows instructions column header', function () {
    Artisan::call('health:check');
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertSee('Instructions');
});

test('page shows remediation for ok checks', function () {
    Artisan::call('health:check');
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertSee('Aucune action requise');
});

test('page shows refresh button', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertSee('Lancer les vérifications');
});

test('refresh runs health checks', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.health.refresh'));
    $response->assertRedirect(route('admin.health'));
});

test('empty state when no results', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertSee('Aucune vérification');
});

test('non-admin gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $response = $this->actingAs($user)->get(route('admin.health'));
    $response->assertForbidden();
});

test('guest redirected to login', function () {
    $response = $this->get(route('admin.health'));
    $response->assertRedirect(route('login'));
});

test('route is registered', function () {
    expect(Route::has('admin.health'))->toBeTrue();
});

test('remediation contains code tags for instructions', function () {
    Artisan::call('health:check');
    $response = $this->actingAs($this->admin)->get(route('admin.health'));
    $response->assertSee('<code>', false);
});
