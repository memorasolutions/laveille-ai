<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;

class TrashController
{
    public function index(): View
    {
        return view('backoffice::trash.index', [
            'title' => 'Corbeille',
            'subtitle' => 'Éléments supprimés',
            'trashedArticles' => Article::onlyTrashed()->latest('deleted_at')->get(),
            'trashedComments' => Comment::onlyTrashed()->with('article')->latest('deleted_at')->get(),
        ]);
    }

    public function restoreArticle(int $id): RedirectResponse
    {
        Article::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Article restauré.');
    }

    public function restoreComment(int $id): RedirectResponse
    {
        Comment::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Commentaire restauré.');
    }

    public function forceDeleteArticle(int $id): RedirectResponse
    {
        Article::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Article supprimé définitivement.');
    }

    public function forceDeleteComment(int $id): RedirectResponse
    {
        Comment::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Commentaire supprimé définitivement.');
    }
}
