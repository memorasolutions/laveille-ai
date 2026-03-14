<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\LookerStudioStats;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

test('stats page loads for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get('/admin/stats')
        ->assertOk()
        ->assertSee('Statistiques');
});

test('stats page denied for non-admin', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/admin/stats')
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get('/admin/stats')
        ->assertRedirect('/login');
});

test('livewire component renders without url', function () {
    Livewire::test(LookerStudioStats::class)
        ->assertOk()
        ->assertSee('Configurer');
});

test('livewire component renders with url', function () {
    Setting::create([
        'group' => 'seo',
        'key' => 'looker_studio_url',
        'value' => 'https://lookerstudio.google.com/embed/reporting/test-123',
        'type' => 'string',
        'description' => 'URL Looker Studio',
    ]);

    Livewire::test(LookerStudioStats::class)
        ->assertOk()
        ->assertSee('lookerstudio.google.com');
});

test('empty url shows setup guide with 3 steps', function () {
    Livewire::test(LookerStudioStats::class)
        ->assertSee('Aucun rapport configuré')
        ->assertSee('Créer un rapport')
        ->assertSee('Copier le lien embed')
        ->assertSee('Coller dans les paramètres');
});

test('security headers include frame-src for looker', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/admin/stats');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain('lookerstudio.google.com');
});

test('sidebar has stats link', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSee('Statistiques');
});

test('iframe has proper sandbox attribute when url set', function () {
    Setting::create([
        'group' => 'seo',
        'key' => 'looker_studio_url',
        'value' => 'https://lookerstudio.google.com/embed/reporting/abc',
        'type' => 'string',
        'description' => 'URL Looker Studio',
    ]);

    Livewire::test(LookerStudioStats::class)
        ->assertSee('sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"', false);
});

test('looker_studio_url setting exists in seeder', function () {
    $this->artisan('db:seed', ['--class' => 'Modules\\Settings\\Database\\Seeders\\SettingsDatabaseSeeder']);

    expect(Setting::where('key', 'looker_studio_url')->exists())->toBeTrue();
});
