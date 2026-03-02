<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

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
        // Standard conversions (original format, optimized)
        $this->addMediaConversion('thumbnail')
            ->nonQueued()
            ->optimize()
            ->fit(Fit::Crop, 150, 150);

        $this->addMediaConversion('medium')
            ->nonQueued()
            ->optimize()
            ->fit(Fit::Contain, 600, 600);

        $this->addMediaConversion('large')
            ->nonQueued()
            ->optimize()
            ->fit(Fit::Contain, 1200, 1200);

        // WebP conversions (modern format, smaller files)
        $this->addMediaConversion('thumbnail-webp')
            ->nonQueued()
            ->optimize()
            ->format('webp')
            ->fit(Fit::Crop, 150, 150);

        $this->addMediaConversion('medium-webp')
            ->nonQueued()
            ->optimize()
            ->format('webp')
            ->fit(Fit::Contain, 600, 600);

        $this->addMediaConversion('large-webp')
            ->nonQueued()
            ->optimize()
            ->format('webp')
            ->fit(Fit::Contain, 1200, 1200);
    }
}
