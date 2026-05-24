<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authors\Http\Resources\PublicAuthorPostResource;
use Modules\Authors\Http\Resources\PublicAuthorResource;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;

final class PublicAuthorApiController extends Controller
{
    public function show(string $slug): JsonResource
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->firstOrFail();

        return new PublicAuthorResource($author);
    }

    public function posts(string $slug): AnonymousResourceCollection
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->firstOrFail();

        $posts = AuthorPost::where('author_profile_id', $author->id)
            ->published()
            ->public()
            ->with('authorProfile')
            ->latest('published_at')
            ->paginate(20);

        return PublicAuthorPostResource::collection($posts);
    }
}
