<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

test('admin peut accéder à la page failed jobs', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.failed-jobs.index'))
        ->assertOk();
});

test('invité redirigé vers login', function () {
    $this->get(route('admin.failed-jobs.index'))
        ->assertRedirect();
});

test('non-admin reçoit 403', function () {
    $this->actingAs($this->user)
        ->get(route('admin.failed-jobs.index'))
        ->assertForbidden();
});

test('page affiche le titre Jobs échoués', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.failed-jobs.index'))
        ->assertSee('Jobs échoués');
});

test('état vide affiche message', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.failed-jobs.index'))
        ->assertSee('Aucun job en échec');
});

test('route retry existe', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.failed-jobs.retry', 999))
        ->assertRedirect();
});

test('route destroy existe', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.failed-jobs.destroy', 999))
        ->assertRedirect();
});

test('route destroy-all existe', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.failed-jobs.destroy-all'))
        ->assertRedirect();
});
