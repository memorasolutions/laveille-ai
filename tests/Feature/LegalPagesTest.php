<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use App\Models\User;

uses(RefreshDatabase::class);

test('legal page loads with 200 status and shows title', function () {
    $response = $this->get(route('legal'));

    $response->assertStatus(200);
    $response->assertSee('Mentions légales');
});

test('legal page shows default fallback text when settings are empty', function () {
    // Create settings with empty values (like the seeder does)
    Setting::create(['key' => 'legal.company_address', 'value' => '', 'group' => 'legal', 'type' => 'string', 'description' => 'test']);
    Setting::create(['key' => 'legal.director_name', 'value' => '', 'group' => 'legal', 'type' => 'string', 'description' => 'test']);

    $response = $this->get(route('legal'));

    $response->assertStatus(200);
    $response->assertSee('Non renseigné');
});

test('legal page shows custom values when settings exist', function () {
    $settings = [
        ['key' => 'legal.company_address', 'value' => '123 rue Principale, Montréal', 'group' => 'legal', 'type' => 'string', 'description' => 'Adresse'],
        ['key' => 'legal.director_name', 'value' => 'Jean Dupont', 'group' => 'legal', 'type' => 'string', 'description' => 'Directeur'],
        ['key' => 'legal.hosting_name', 'value' => 'Hébergement Québec Inc.', 'group' => 'legal', 'type' => 'string', 'description' => 'Hébergeur'],
        ['key' => 'legal.hosting_address', 'value' => '456 boulevard Laval', 'group' => 'legal', 'type' => 'string', 'description' => 'Adresse hébergeur'],
        ['key' => 'legal.hosting_phone', 'value' => '+1 514 555-1234', 'group' => 'legal', 'type' => 'string', 'description' => 'Téléphone hébergeur'],
    ];

    foreach ($settings as $s) {
        Setting::create($s);
    }

    $response = $this->get(route('legal'));

    $response->assertStatus(200);
    $response->assertSee('123 rue Principale, Montréal', false);
    $response->assertSee('Jean Dupont', false);
    $response->assertSee('Hébergement Québec Inc.', false);
    $response->assertSee('456 boulevard Laval', false);
    $response->assertSee('+1 514 555-1234', false);
});

test('privacy page loads with 200 status', function () {
    $response = $this->get(route('privacy'));

    $response->assertStatus(200);
});

test('terms page loads with 200 status', function () {
    $response = $this->get(route('terms'));

    $response->assertStatus(200);
});

test('footer contains links to legal pages', function () {
    $response = $this->get(route('blog.index'));

    $response->assertStatus(200);
    $response->assertSee(route('legal'), false);
    $response->assertSee(route('privacy'), false);
    $response->assertSee(route('terms'), false);
});

test('admin settings page shows legal tab for super admin', function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('admin.settings.index'));

    $response->assertStatus(200);
    $response->assertSee('Légal', false);
});

test('seeder creates all 5 legal settings', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    $legalSettings = Setting::where('group', 'legal')->get();

    expect($legalSettings)->toHaveCount(5);

    $expectedKeys = [
        'legal.company_address',
        'legal.director_name',
        'legal.hosting_name',
        'legal.hosting_address',
        'legal.hosting_phone',
    ];

    foreach ($expectedKeys as $key) {
        expect($legalSettings->pluck('key'))->toContain($key);
    }
});
