<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makePhase33Admin(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

test('can export users CSV', function () {
    $this->actingAs(makePhase33Admin())
        ->get('/admin/export/users')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('can export roles CSV', function () {
    $this->actingAs(makePhase33Admin())
        ->get('/admin/export/roles')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('can export settings CSV', function () {
    $this->actingAs(makePhase33Admin())
        ->get('/admin/export/settings')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('search returns results page', function () {
    $this->actingAs(makePhase33Admin())
        ->get('/admin/search?q=admin')
        ->assertOk()
        ->assertSee('Résultats');
});

test('search with empty query returns ok', function () {
    $this->actingAs(makePhase33Admin())
        ->get('/admin/search')
        ->assertOk();
});

test('unauthenticated export redirects to login', function () {
    $this->get('/admin/export/users')
        ->assertRedirect('/login');
});
