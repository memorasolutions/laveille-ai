<?php

declare(strict_types=1);

test('horizon package is installed', function () {
    expect(class_exists(\Laravel\Horizon\Horizon::class))->toBeTrue();
});

test('telescope package is installed', function () {
    expect(class_exists(\Laravel\Telescope\Telescope::class))->toBeTrue();
});

test('response cache package is installed', function () {
    expect(class_exists(\Spatie\ResponseCache\ResponseCache::class))->toBeTrue();
});

test('backup package is installed', function () {
    expect(class_exists(\Spatie\Backup\BackupServiceProvider::class))->toBeTrue();
});

test('horizon config exists', function () {
    expect(config('horizon'))->not->toBeNull();
});

test('backup config exists', function () {
    expect(config('backup'))->not->toBeNull();
});

test('responsecache config exists', function () {
    expect(config('responsecache'))->not->toBeNull();
});
