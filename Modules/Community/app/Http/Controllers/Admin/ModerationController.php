<?php

declare(strict_types=1);

namespace Modules\Community\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Community\Models\Comment;
use Modules\Community\Models\Review;

class ModerationController extends Controller
{
    public function index(): View
    {
        $pendingComments = Comment::with('user')->pending()->orderByDesc('created_at')->get();
        $pendingReviews = Review::with('user')->pending()->orderByDesc('created_at')->get();

        return view('community::admin.moderation', compact('pendingComments', 'pendingReviews'));
    }

    public function moderate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:comment,review',
            'id' => 'required|integer',
            'action' => 'required|in:approve,reject',
        ]);

        $status = $validated['action'] === 'approve' ? 'approved' : 'rejected';

        if ($validated['type'] === 'comment') {
            Comment::where('id', $validated['id'])->update(['status' => $status]);
        } else {
            Review::where('id', $validated['id'])->update(['status' => $status]);
        }

        $label = $validated['action'] === 'approve' ? __('approuvé') : __('rejeté');

        return redirect()->route('admin.community.moderation')->with('success', __('Élément :action.', ['action' => $label]));
    }
}
