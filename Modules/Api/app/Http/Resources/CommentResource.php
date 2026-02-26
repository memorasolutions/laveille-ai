<?php

declare(strict_types=1);

namespace Modules\Api\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Modules\Blog\Models\Comment */
final class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->authorName(),
            'content' => $this->content,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
