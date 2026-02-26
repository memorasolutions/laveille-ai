<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Services\MediaService;
use Modules\Media\Traits\HasMediaAttachments;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('media service is registered as singleton', function () {
    $service1 = app(MediaService::class);
    $service2 = app(MediaService::class);

    expect($service1)->toBeInstanceOf(MediaService::class);
    expect($service1)->toBe($service2);
});

test('HasMediaAttachments trait exists and uses InteractsWithMedia', function () {
    expect(trait_exists(HasMediaAttachments::class))->toBeTrue();

    $traits = class_uses(HasMediaAttachments::class);
    expect($traits)->toContain(\Spatie\MediaLibrary\InteractsWithMedia::class);
});

test('media model class is available', function () {
    expect(class_exists(Media::class))->toBeTrue();
});

test('media service get all returns collection', function () {
    $service = app(MediaService::class);

    $result = $service->getAllMedia();

    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('media service delete returns false for nonexistent media', function () {
    $service = app(MediaService::class);

    expect($service->deleteMedia(99999))->toBeFalse();
});

test('HasMediaAttachments trait defines media conversions', function () {
    $content = file_get_contents(base_path('Modules/Media/app/Traits/HasMediaAttachments.php'));

    expect($content)->toContain('registerMediaConversions')
        ->toContain('thumbnail')
        ->toContain('medium')
        ->toContain('large');
});

test('HasMediaAttachments trait has avatar collection', function () {
    $content = file_get_contents(base_path('Modules/Media/app/Traits/HasMediaAttachments.php'));

    expect($content)->toContain("'avatar'")
        ->toContain('singleFile');
});

test('media service has all required methods', function () {
    $service = app(MediaService::class);
    $methods = get_class_methods($service);

    expect($methods)->toContain('addMedia')
        ->toContain('addMediaFromUrl')
        ->toContain('getMedia')
        ->toContain('getFirstMedia')
        ->toContain('getFirstMediaUrl')
        ->toContain('deleteMedia')
        ->toContain('clearMediaCollection')
        ->toContain('getAllMedia');
});
