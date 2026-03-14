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
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin->assignRole($role);
    $this->actingAs($this->admin);
});

it('admin navbar contient global search', function () {
    $this->get('/admin')
        ->assertStatus(200)
        ->assertSee('Rechercher');
});

it('admin navbar contient notification bell livewire', function () {
    $this->get('/admin')
        ->assertStatus(200)
        ->assertSee('wire:poll');
});

it('admin dashboard se charge correctement', function () {
    $this->get('/admin')
        ->assertStatus(200);
});

it('dark mode toggle présent dans navbar', function () {
    $this->get('/admin')
        ->assertStatus(200)
        ->assertSee('data-bs-theme', false);
});

it('profile dropdown affiche nom utilisateur', function () {
    $this->get('/admin')
        ->assertStatus(200)
        ->assertSee($this->admin->name);
});
