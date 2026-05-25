<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;

final class DraftPreviewController extends Controller
{
    public function show(string $slug, string $postSlug): View
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->with('user')
            ->firstOrFail();

        $post = AuthorPost::where('author_profile_id', $author->id)
            ->where('slug', $postSlug)
            ->firstOrFail();

        $graph = [];
        if (function_exists('lv_jsonld_author_from_profile')) {
            $graph[] = lv_jsonld_author_from_profile($author);
        }
        $jsonLd = ['@context' => 'https://schema.org', '@graph' => $graph];

        return view('authors::mini-site.post', [
            'title' => $post->title,
            'author' => $author,
            'post' => $post,
            'jsonLd' => $jsonLd,
            'isDraftPreview' => true,
        ]);
    }
}
