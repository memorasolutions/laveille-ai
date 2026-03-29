<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\News\Models\NewsArticle;

class PublicNewsController extends Controller
{
    public function index(): View
    {
        $articles = NewsArticle::published()
            ->recent()
            ->with('source')
            ->paginate(20);

        return view('news::public.index', compact('articles'));
    }

    public function show(NewsArticle $article): View
    {
        abort_if(! $article->is_published, 404);

        $article->load('source');

        return view('news::public.show', compact('article'));
    }
}
