<?php

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuthorController extends Controller
{
    public function show(string $slug): View|Response
    {
        $authors = (array) trans('fronttheme::authors');

        if (! is_array($authors) || ! isset($authors[$slug]) || ! is_array($authors[$slug])) {
            abort(404);
        }

        $author = $authors[$slug];

        $articles = collect();
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            try {
                $articles = \Modules\Blog\Models\Article::published()
                    ->latest('published_at')
                    ->limit(6)
                    ->get();
            } catch (\Throwable) {
                $articles = collect();
            }
        }

        // Pre-encode Schema.org JSON-LD (évite conflit Blade @context directive)
        $schemaJson = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author['name'] ?? '',
            'description' => $author['bio'] ?? '',
            'url' => route('author.show', $slug),
            'image' => asset('images/logo-avatar.png'),
            'jobTitle' => $author['role'] ?? '',
            'sameAs' => array_values(array_filter([
                $author['linkedin'] ?? null,
                $author['twitter'] ?? null,
                $author['website'] ?? null,
            ])),
            'worksFor' => [
                '@type' => 'Organization',
                'name' => 'MEMORA solutions',
                'url' => 'https://memora.solutions',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('fronttheme::author.show', compact('author', 'slug', 'articles', 'schemaJson'));
    }
}
