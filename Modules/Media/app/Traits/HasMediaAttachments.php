<?php

declare(strict_types=1);

namespace Modules\Media\Traits;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasMediaAttachments
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default');

        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv',
            ]);

        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->nonQueued()
            ->fit(Fit::Crop, 150, 150);

        $this->addMediaConversion('medium')
            ->nonQueued()
            ->fit(Fit::Contain, 600, 600);

        $this->addMediaConversion('large')
            ->nonQueued()
            ->fit(Fit::Contain, 1200, 1200);
    }
}
