<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicAuthorPostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'cover_image' => $this->cover_image ? url($this->cover_image) : null,
            'tags' => $this->tags ?? [],
            'reading_time_minutes' => $this->reading_time_minutes,
            'views_count' => $this->views_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'links' => [
                'self' => $this->authorProfile
                    ? url('/@'.$this->authorProfile->slug.'/'.$this->slug)
                    : null,
            ],
        ];
    }
}
