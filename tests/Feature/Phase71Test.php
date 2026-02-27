<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\RolesTable;
use Modules\Backoffice\Livewire\SettingsTable;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
    $this->actingAs($this->admin);
});

// === ROLES TABLE ===

test('roles table monte sans erreur', function () {
    Livewire::test(RolesTable::class)->assertOk();
});

test('roles table recherche filtre par nom', function () {
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::test(RolesTable::class)
        ->set('search', 'editor')
        ->assertSee('editor');
});

test('roles table recherche masque les non-correspondants', function () {
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

    Livewire::test(RolesTable::class)
        ->set('search', 'editor')
        ->assertSee('editor')
        ->assertDontSee('viewer');
});

test('roles table tri fonctionne', function () {
    Livewire::test(RolesTable::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name');
});

// === SETTINGS TABLE ===

test('settings table monte sans erreur', function () {
    Livewire::test(SettingsTable::class)->assertOk();
});

test('settings table recherche par clé', function () {
    Setting::create(['key' => 'app_name', 'value' => 'Test', 'group' => 'general']);

    Livewire::test(SettingsTable::class)
        ->set('search', 'app_name')
        ->assertSee('app_name');
});

test('settings table filtre par groupe', function () {
    Setting::create(['key' => 'mail_host', 'value' => 'smtp', 'group' => 'mail']);
    Setting::create(['key' => 'other_key', 'value' => 'x', 'group' => 'other']);

    Livewire::test(SettingsTable::class)
        ->set('filterGroup', 'mail')
        ->assertSee('mail_host')
        ->assertDontSee('other_key');
});

test('settings table resetFilters remet à zéro', function () {
    Livewire::test(SettingsTable::class)
        ->set('search', 'test')
        ->set('filterGroup', 'general')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('filterGroup', '');
});
