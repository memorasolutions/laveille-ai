<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Blog\Models\Article;

class FeedController extends Controller
{
    public function feed(): Response
    {
        $articles = Article::query()
            ->published()
            ->latest('published_at')
            ->limit(20)
            ->get();

        return response()
            ->view('blog::feed.rss', compact('articles'), 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
