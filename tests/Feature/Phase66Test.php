<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    $this->actingAs($this->admin);
});

test('export users retourne un CSV', function () {
    $this->get(route('admin.export.users'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('export roles retourne un CSV', function () {
    $this->get(route('admin.export.roles'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('export settings retourne un CSV', function () {
    $this->get(route('admin.export.settings'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('page import users est accessible', function () {
    $this->get(route('admin.import.users'))
        ->assertOk()
        ->assertSee('Importer');
});

test('import CSV crée les nouveaux utilisateurs', function () {
    $csv = "id,name,email,created_at\n999,Test Import,import@test.com,2026-01-01\n";
    $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $this->post(route('admin.import.users.store'), ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'import@test.com', 'name' => 'Test Import']);
});

test('import CSV ignore les emails existants', function () {
    User::factory()->create(['email' => 'existing@test.com']);
    $count = User::count();

    $csv = "id,name,email,created_at\n1,Existing,existing@test.com,2026-01-01\n";
    $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $this->post(route('admin.import.users.store'), ['file' => $file]);

    expect(User::count())->toBe($count);
});

test('page users affiche les boutons export et import', function () {
    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Exporter CSV')
        ->assertSee('Importer CSV');
});
