<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;

class CommentController extends Controller
{
    public function store(Request $request, Article $article): RedirectResponse
    {
        $rules = [
            'content' => 'required|string|min:3|max:2000',
        ];

        if (! auth()->check()) {
            $rules['guest_name'] = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
        }

        $validated = $request->validate($rules);

        $data = [
            'article_id' => $article->id,
            'content' => $validated['content'],
            'status' => 'pending',
        ];

        if (auth()->check()) {
            $data['user_id'] = auth()->id();
        } else {
            $data['guest_name'] = $validated['guest_name'];
            $data['guest_email'] = $validated['guest_email'];
        }

        Comment::create($data);

        return redirect()->back()->with('success', 'Votre commentaire a été soumis et attend validation.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);
        $comment->delete();

        return redirect()->back()->with('success', 'Commentaire supprimé.');
    }
}
