<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Blog\Models\Article;

class AuthorController extends Controller
{
    public function show(User $user): View
    {
        $articles = Article::published()
            ->where('user_id', $user->id)
            ->latest('published_at')
            ->paginate(9);

        $totalArticles = Article::published()->where('user_id', $user->id)->count();

        return view('blog::public.author', compact('user', 'articles', 'totalArticles'));
    }
}
