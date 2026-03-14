<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Modules\Blog\Models\Article */
final class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt ?? str($this->content)->limit(160)->toString(),
            'category' => $this->when($this->relationLoaded('blogCategory') && $this->blogCategory, [
                'id' => $this->blogCategory?->id,
                'name' => $this->blogCategory?->name,
                'slug' => $this->blogCategory?->slug,
            ], $this->category),
            'tags' => $this->tags ?? [],
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => $this->when($this->relationLoaded('user') && $this->user, [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'comments_count' => $this->whenCounted('comments'),
            'url' => url('/api/v1/articles/'.$this->slug),
        ];
    }
}
