<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Backup\Services\BackupService;

uses(Tests\TestCase::class);

test('backup service is registered as singleton', function () {
    $service1 = app(BackupService::class);
    $service2 = app(BackupService::class);

    expect($service1)->toBeInstanceOf(BackupService::class);
    expect($service1)->toBe($service2);
});

test('backup service returns array of backups', function () {
    $service = app(BackupService::class);
    $backups = $service->getBackups();

    expect($backups)->toBeArray();
});

test('backup package is available', function () {
    expect(class_exists(\Spatie\Backup\BackupServiceProvider::class))->toBeTrue();
});

test('backup config exists', function () {
    expect(config('backup.backup.name'))->not->toBeNull();
    expect(config('backup.backup.destination.disks'))->toBeArray();
});

test('backup service can list disks', function () {
    $disks = config('backup.backup.destination.disks');
    expect($disks)->toBeArray();
    expect($disks)->not->toBeEmpty();
});

test('backup artisan commands are registered', function () {
    $this->artisan('list')->assertSuccessful();

    expect(collect(\Illuminate\Support\Facades\Artisan::all())->has('backup:run'))->toBeTrue();
    expect(collect(\Illuminate\Support\Facades\Artisan::all())->has('backup:clean'))->toBeTrue();
});
