<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('scheduler page accessible by admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.scheduler'))
        ->assertOk();
});

test('login history page accessible by admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.login-history'))
        ->assertOk();
});

test('mail log page accessible by admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.mail-log'))
        ->assertOk();
});

test('security dashboard accessible by admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.security'))
        ->assertOk();
});

test('cache page accessible by admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.cache'))
        ->assertOk();
});

test('cache clear works', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.cache.clear-cache'))
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('cache clear all works', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.cache.clear-all'))
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('monitoring pages redirect unauthenticated', function () {
    $this->get(route('admin.scheduler'))->assertRedirect();
    $this->get(route('admin.login-history'))->assertRedirect();
    $this->get(route('admin.mail-log'))->assertRedirect();
    $this->get(route('admin.security'))->assertRedirect();
    $this->get(route('admin.cache'))->assertRedirect();
});
