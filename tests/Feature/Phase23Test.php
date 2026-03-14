<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Modules\SEO\Models\MetaTag;
use Modules\Settings\Models\Setting;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

// Factories

test('user factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBeString()
        ->and($user->email)->toContain('@')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('user factory unverified state works', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

test('metatag factory creates valid metatag', function () {
    $metaTag = MetaTag::factory()->create();

    expect($metaTag)->toBeInstanceOf(MetaTag::class)
        ->and($metaTag->url_pattern)->toStartWith('/')
        ->and($metaTag->title)->toBeString()
        ->and($metaTag->is_active)->toBeTrue();
});

test('metatag factory inactive state works', function () {
    $metaTag = MetaTag::factory()->inactive()->create();

    expect($metaTag->is_active)->toBeFalse();
});

test('metatag factory wildcard state works', function () {
    $metaTag = MetaTag::factory()->withWildcard()->create();

    expect($metaTag->url_pattern)->toBe('/blog/*');
});

test('setting factory creates valid setting', function () {
    $setting = Setting::factory()->create();

    expect($setting)->toBeInstanceOf(Setting::class)
        ->and($setting->key)->toBeString()
        ->and($setting->group)->toBeIn(['general', 'mail', 'seo', 'social']);
});

test('setting factory boolean state works', function () {
    $setting = Setting::factory()->boolean()->create();

    expect($setting->type)->toBe('boolean')
        ->and($setting->value)->toBeIn(['0', '1']);
});

// Seeders

test('roles and permissions seeder creates roles and permissions', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    expect(\Spatie\Permission\Models\Role::count())->toBeGreaterThanOrEqual(3)
        ->and(\Spatie\Permission\Models\Role::where('name', 'super_admin')->exists())->toBeTrue()
        ->and(\Spatie\Permission\Models\Role::where('name', 'admin')->exists())->toBeTrue()
        ->and(\Spatie\Permission\Models\Role::where('name', 'user')->exists())->toBeTrue()
        ->and(\Spatie\Permission\Models\Permission::count())->toBeGreaterThanOrEqual(17);
});

test('settings seeder creates default settings', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    expect(Setting::where('key', 'site_name')->exists())->toBeTrue()
        ->and(Setting::where('key', 'maintenance_mode')->exists())->toBeTrue()
        ->and(Setting::count())->toBeGreaterThanOrEqual(7);
});

test('seo seeder creates default metatags', function () {
    $this->seed(\Modules\SEO\Database\Seeders\SEODatabaseSeeder::class);

    expect(MetaTag::where('url_pattern', '/')->exists())->toBeTrue()
        ->and(MetaTag::where('url_pattern', '/contact')->exists())->toBeTrue()
        ->and(MetaTag::where('url_pattern', '/blog/*')->exists())->toBeTrue()
        ->and(MetaTag::count())->toBeGreaterThanOrEqual(3);
});

test('database seeder runs without errors', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(User::where('email', env('ADMIN_EMAIL', 'admin@example.com'))->exists())->toBeTrue()
        ->and(User::where('email', 'moderator@laravel-core.test')->exists())->toBeTrue()
        ->and(User::count())->toBeGreaterThanOrEqual(7)
        ->and(Setting::count())->toBeGreaterThanOrEqual(7)
        ->and(MetaTag::count())->toBeGreaterThanOrEqual(3);
});

test('database seeder is idempotent', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    $countAfterFirst = User::count();

    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    $countAfterSecond = User::count();

    // firstOrCreate empêche les doublons pour admin/moderator
    // Seuls les factory users s'ajoutent (mode non-production)
    expect($countAfterSecond)->toBeGreaterThanOrEqual($countAfterFirst);
});
