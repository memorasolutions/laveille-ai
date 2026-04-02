<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Blog\Models\Article;

class FeedController extends Controller
{
    public function rss()
    {
        $articles = Article::published()->orderBy('published_at', 'desc')->take(30)->get();

        return response()->view('blog::feed.rss', compact('articles'), 200, [
            'Content-Type' => 'application/rss+xml',
        ]);
    }
}
