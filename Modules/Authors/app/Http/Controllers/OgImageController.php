<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Services\OgImageService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OgImageController extends Controller
{
    public function show(OgImageService $service, string $slug, string $postSlug): BinaryFileResponse|Response
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->with('user')
            ->firstOrFail();

        $post = AuthorPost::published()
            ->public()
            ->where('author_profile_id', $author->id)
            ->where('slug', $postSlug)
            ->firstOrFail();

        $path = $service->generate($post, $author);

        if ($path === null || ! is_file($path)) {
            return response('OG image unavailable', Response::HTTP_NOT_FOUND);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400, s-maxage=604800',
        ]);
    }
}
