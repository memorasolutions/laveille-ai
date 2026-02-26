<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Facades\Settings;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\SettingsService;

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

// --- SettingsService tests ---

test('settings service is registered as singleton', function () {
    $service1 = app(SettingsService::class);
    $service2 = app(SettingsService::class);

    expect($service1)->toBeInstanceOf(SettingsService::class);
    expect($service1)->toBe($service2);
});

test('settings service get and set', function () {
    $service = app(SettingsService::class);
    $service->set('facade_test', 'works');

    expect($service->get('facade_test'))->toBe('works');
});

test('settings service has method', function () {
    $service = app(SettingsService::class);
    $service->set('exists_key', 'value');

    expect($service->has('exists_key'))->toBeTrue();
    expect($service->has('nope'))->toBeFalse();
});

test('settings service forget method', function () {
    $service = app(SettingsService::class);
    $service->set('delete_me', 'value');

    expect($service->forget('delete_me'))->toBeTrue();
    expect(Setting::where('key', 'delete_me')->exists())->toBeFalse();
});

test('settings service all method', function () {
    $service = app(SettingsService::class);
    $service->set('key1', 'val1', 'string', 'group_a');
    $service->set('key2', 'val2', 'string', 'group_a');
    $service->set('key3', 'val3', 'string', 'group_b');

    $all = $service->all();
    expect($all)->toHaveKey('key1')->toHaveKey('key2')->toHaveKey('key3');

    $groupA = $service->all('group_a');
    expect($groupA)->toHaveKey('key1')->toHaveKey('key2')->not->toHaveKey('key3');
});

// --- Facade tests ---

test('settings facade is accessible', function () {
    expect(class_exists(Settings::class))->toBeTrue();
});

test('settings facade get and set work', function () {
    Settings::set('facade_key', 'facade_value');

    expect(Settings::get('facade_key'))->toBe('facade_value');
});

test('settings facade has method works', function () {
    Settings::set('check_key', 'yes');

    expect(Settings::has('check_key'))->toBeTrue();
    expect(Settings::has('missing_key'))->toBeFalse();
});
