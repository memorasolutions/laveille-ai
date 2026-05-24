<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicAuthorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'slug' => $this->slug,
            'display_name' => $this->display_name ?? $this->slug,
            'bio' => $this->bio,
            'profile_image' => $this->profile_image ? url('storage/'.$this->profile_image) : null,
            'qualifications' => $this->qualifications ?? [],
            'social_links' => $this->social_links ?? [],
            'tier' => $this->tier,
            'links' => [
                'mini_site' => url('/@'.$this->slug),
                'rss' => url('/@'.$this->slug.'/feed.xml'),
                'json_feed' => url('/@'.$this->slug.'/feed.json'),
            ],
        ];
    }
}
