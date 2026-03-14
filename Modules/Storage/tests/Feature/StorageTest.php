<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Storage\Services\StorageService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('storage service is registered as singleton', function () {
    $service1 = app(StorageService::class);
    $service2 = app(StorageService::class);

    expect($service1)->toBeInstanceOf(StorageService::class);
    expect($service1)->toBe($service2);
});

test('storage service can upload a file', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('document.pdf', 100);
    $service = app(StorageService::class);

    $path = $service->upload($file, 'documents', 'public');

    expect($path)->toStartWith('documents/');
    Storage::disk('public')->assertExists($path);
});

test('storage service can delete a file', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('test.txt', 10);
    $service = app(StorageService::class);
    $path = $service->upload($file, '', 'public');

    expect($service->exists($path, 'public'))->toBeTrue();

    $service->delete($path, 'public');

    expect($service->exists($path, 'public'))->toBeFalse();
});

test('storage service can check file existence', function () {
    Storage::fake('public');

    $service = app(StorageService::class);

    expect($service->exists('nonexistent.txt', 'public'))->toBeFalse();

    $file = UploadedFile::fake()->create('exists.txt', 5);
    $path = $service->upload($file, '', 'public');

    expect($service->exists($path, 'public'))->toBeTrue();
});

test('storage service can list files', function () {
    Storage::fake('public');

    $service = app(StorageService::class);

    UploadedFile::fake()->create('a.txt', 5)->storeAs('docs', 'a.txt', 'public');
    UploadedFile::fake()->create('b.txt', 5)->storeAs('docs', 'b.txt', 'public');

    $files = $service->files('docs', 'public');

    expect($files)->toHaveCount(2);
});

test('storage service can get disk usage', function () {
    Storage::fake('public');

    $service = app(StorageService::class);

    Storage::disk('public')->put('file.txt', str_repeat('x', 1024));

    $usage = $service->diskUsage('public');

    expect($usage)->toHaveKeys(['files_count', 'total_size', 'total_size_human']);
    expect($usage['files_count'])->toBe(1);
    expect($usage['total_size'])->toBe(1024);
});

test('storage service can move a file', function () {
    Storage::fake('public');

    $service = app(StorageService::class);

    UploadedFile::fake()->create('original.txt', 5)->storeAs('', 'original.txt', 'public');

    $result = $service->move('original.txt', 'moved.txt', 'public');

    expect($result)->toBeTrue();
    expect($service->exists('original.txt', 'public'))->toBeFalse();
    expect($service->exists('moved.txt', 'public'))->toBeTrue();
});

test('storage service can copy a file', function () {
    Storage::fake('public');

    $service = app(StorageService::class);

    UploadedFile::fake()->create('source.txt', 5)->storeAs('', 'source.txt', 'public');

    $result = $service->copy('source.txt', 'copy.txt', 'public');

    expect($result)->toBeTrue();
    expect($service->exists('source.txt', 'public'))->toBeTrue();
    expect($service->exists('copy.txt', 'public'))->toBeTrue();
});
