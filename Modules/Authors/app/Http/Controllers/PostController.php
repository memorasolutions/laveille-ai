<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;

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

        $post->increment('views_count');

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Article',
                    '@id' => url()->current().'#article',
                    'headline' => $post->title,
                    'description' => $post->excerpt,
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'dateModified' => $post->updated_at->toIso8601String(),
                    'author' => function_exists('lv_jsonld_author_from_profile')
                        ? lv_jsonld_author_from_profile($author)
                        : ['@type' => 'Person', 'name' => $author->display_name ?? $author->slug],
                    'image' => $post->cover_image ? [url($post->cover_image)] : null,
                    'wordCount' => str_word_count(strip_tags((string) $post->body_html)),
                    'keywords' => implode(', ', $post->tags ?? []),
                    'mainEntityOfPage' => url()->current(),
                    'inLanguage' => app()->getLocale(),
                ],
            ],
        ];

        if (function_exists('lv_jsonld_author_website')) {
            $jsonLd['@graph'][] = lv_jsonld_author_website($author);
        }

        return view('authors::mini-site.post', compact('author', 'post', 'jsonLd'));
    }
}
