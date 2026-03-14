<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Blog\Models\Comment;
use Modules\Blog\States\ApprovedCommentState;
use Modules\Blog\States\SpamCommentState;

class CommentAdminController extends Controller
{
    public function index(): View
    {
        return view('blog::admin.comments.index');
    }

    public function approve(Comment $comment): RedirectResponse
    {
        $comment->status->transitionTo(ApprovedCommentState::class);

        return redirect()->back()->with('success', 'Commentaire approuvé.');
    }

    public function spam(Comment $comment): RedirectResponse
    {
        $comment->status->transitionTo(SpamCommentState::class);

        return redirect()->back()->with('success', 'Commentaire marqué comme spam.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->forceDelete();

        return redirect()->back()->with('success', 'Commentaire supprimé définitivement.');
    }
}
