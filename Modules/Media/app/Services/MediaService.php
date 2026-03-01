<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService
{
    public function addMedia(HasMedia $model, UploadedFile $file, string $collection = 'default'): Media
    {
        return $model->addMedia($file)->toMediaCollection($collection);
    }

    public function addMediaFromUrl(HasMedia $model, string $url, string $collection = 'default'): Media
    {
        return $model->addMediaFromUrl($url)->toMediaCollection($collection);
    }

    public function getMedia(HasMedia $model, string $collection = 'default'): \Illuminate\Support\Collection
    {
        return $model->getMedia($collection);
    }

    public function getFirstMedia(HasMedia $model, string $collection = 'default'): ?Media
    {
        return $model->getFirstMedia($collection);
    }

    public function getFirstMediaUrl(HasMedia $model, string $collection = 'default', string $conversion = ''): string
    {
        return $model->getFirstMediaUrl($collection, $conversion);
    }

    public function deleteMedia(int $mediaId): bool
    {
        $media = Media::find($mediaId);

        if (! $media) {
            return false;
        }

        $media->delete();

        return true;
    }

    public function clearMediaCollection(HasMedia $model, string $collection = 'default'): void
    {
        $model->clearMediaCollection($collection);
    }

    public function getAllMedia(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Media::with('model')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
