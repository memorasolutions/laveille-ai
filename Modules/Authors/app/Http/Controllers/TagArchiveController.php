<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;

final class TagArchiveController extends Controller
{
    public function show(string $slug, string $tag): View
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->with('user')
            ->firstOrFail();

        $tag = trim(urldecode($tag));

        if ($tag === '') {
            abort(404);
        }

        $posts = AuthorPost::published()
            ->public()
            ->where('author_profile_id', $author->id)
            ->whereJsonContains('tags', $tag)
            ->orderByDesc('published_at')
            ->paginate(12);

        if ($posts->total() === 0) {
            abort(404);
        }

        $breadcrumb = function_exists('lv_jsonld_breadcrumb')
            ? lv_jsonld_breadcrumb([
                ['name' => 'Accueil', 'url' => url('/')],
                ['name' => $author->display_name ?? $author->slug, 'url' => route('authors.mini-site.show', $author->slug)],
                ['name' => 'Tag '.$tag, 'url' => url()->current()],
            ])
            : null;

        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Articles : '.$tag,
            'url' => url()->current(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => (string) config('app.name'),
                'url' => url('/'),
            ],
            'breadcrumb' => $breadcrumb,
        ]);

        return view('authors::mini-site.tag-archive', compact('author', 'tag', 'posts', 'jsonLd'));
    }
}
