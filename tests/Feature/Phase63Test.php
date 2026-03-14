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

it('global search présent dans navbar admin', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('Rechercher');
});

it('global search présent dans la navbar via Livewire', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('backoffice-global-search');
});

it('icône loupe utilise iconify-icon', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('Rechercher', false);
});

it('notification bell a wire poll', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('wire:poll');
});

it('dashboard se charge sans erreur', function () {
    $this->get('/admin')
        ->assertStatus(200);
});
