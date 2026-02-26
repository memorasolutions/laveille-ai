<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

afterEach(function () {
    if (app()->isDownForMaintenance()) {
        Artisan::call('up');
    }
});

test('admin peut toggler le mode maintenance', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.maintenance.toggle'))
        ->assertRedirect();
});

test('invité redirigé vers login pour maintenance', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect();
});

test('non-admin reçoit 403 pour maintenance', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('dashboard affiche indicateur maintenance', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSee('maintenance');
});

test('route maintenance toggle existe', function () {
    expect(route('admin.maintenance.toggle'))->toBeString();
});
