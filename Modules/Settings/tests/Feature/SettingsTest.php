<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('setting can be created', function () {
    $setting = Setting::create([
        'key' => 'test_key',
        'value' => 'test_value',
        'group' => 'general',
        'type' => 'string',
    ]);

    expect($setting->key)->toBe('test_key');
    expect($setting->value)->toBe('test_value');
});

test('setting can be retrieved via static get', function () {
    Setting::create([
        'key' => 'site_name',
        'value' => 'Mon Site',
        'type' => 'string',
    ]);

    expect(Setting::get('site_name'))->toBe('Mon Site');
});

test('setting get returns default when not found', function () {
    expect(Setting::get('nonexistent', 'default'))->toBe('default');
});

test('setting can be set via static set', function () {
    Setting::set('new_key', 'new_value');

    expect(Setting::where('key', 'new_key')->first()->value)->toBe('new_value');
});

test('setting boolean type is cast correctly', function () {
    Setting::create([
        'key' => 'is_active',
        'value' => 'true',
        'type' => 'boolean',
    ]);

    expect(Setting::get('is_active'))->toBeTrue();
});

test('setting integer type is cast correctly', function () {
    Setting::create([
        'key' => 'max_items',
        'value' => '42',
        'type' => 'integer',
    ]);

    expect(Setting::get('max_items'))->toBe(42);
});

test('settings seeder creates default settings', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    expect(Setting::where('key', 'site_name')->exists())->toBeTrue();
    expect(Setting::where('key', 'meta_title')->exists())->toBeTrue();
});
