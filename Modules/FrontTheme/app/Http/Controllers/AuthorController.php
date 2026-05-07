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

        return view('fronttheme::author.show', compact('author', 'slug', 'articles'));
    }
}
