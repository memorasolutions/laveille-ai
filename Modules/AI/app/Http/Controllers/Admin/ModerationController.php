<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Blog\Models\Comment;
use Modules\Blog\States\ApprovedCommentState;
use Modules\Blog\States\SpamCommentState;

class ModerationController extends Controller
{
    public function batch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'exists:blog_comments,id',
            'action' => 'required|in:approve,spam',
        ]);

        /** @var array<int, int> $commentIds */
        $commentIds = $validated['comment_ids'];
        $action = $validated['action'];
        $count = 0;

        foreach ($commentIds as $commentId) {
            $comment = Comment::find($commentId);

            if (! $comment) {
                continue;
            }

            $targetState = $action === 'approve' ? ApprovedCommentState::class : SpamCommentState::class;

            $comment->status->transitionTo($targetState);
            $count++;
        }

        $label = $action === 'approve' ? 'approuvé(s)' : 'marqué(s) comme spam';

        return redirect()->back()->with('success', "{$count} commentaire(s) {$label}.");
    }
}
