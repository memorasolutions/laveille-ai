<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Core\Services\ViewCounterService;

final class PostController extends Controller
{
    public function show(string $slug, string $postSlug): View
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->firstOrFail();

        $post = AuthorPost::where('author_profile_id', $author->id)
            ->where('slug', $postSlug)
            ->published()
            ->public()
            ->firstOrFail();

        // Incident 2026-08-13 : increment views_count délégué au service partagé
        // (filtre robots réel + déduplication rapprochée, jamais de casse de page).
        ViewCounterService::record($post, 'views_count');

        $graph = [];

        if (function_exists('lv_jsonld_blog_posting')) {
            $graph[] = lv_jsonld_blog_posting($post, $author);
        }

        if (function_exists('lv_jsonld_breadcrumb')) {
            $graph[] = lv_jsonld_breadcrumb([
                ['name' => 'Accueil', 'url' => url('/')],
                ['name' => $author->display_name ?? $author->slug, 'url' => url('/@'.$author->slug)],
                ['name' => $post->title, 'url' => url()->current()],
            ]);
        }

        if (function_exists('lv_jsonld_author_website')) {
            $graph[] = lv_jsonld_author_website($author);
        }

        $jsonLd = ['@context' => 'https://schema.org', '@graph' => $graph];

        return view('authors::mini-site.post', compact('author', 'post', 'jsonLd'));
    }
}
