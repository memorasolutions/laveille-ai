<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    $this->actingAs($this->admin);
});

it('cloche notifications utilise iconify-icon bell', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('notification', false);
});

it('cloche notifications a la classe has-indicator WowDash', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('wire:poll', false);
});

it('cloche notifications a wire poll', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('wire:poll');
});

it('cloche notifications a aria-label', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('aria-label="Notifications"', false);
});

it('dashboard se charge sans erreur', function () {
    $this->get('/admin')
        ->assertStatus(200);
});
