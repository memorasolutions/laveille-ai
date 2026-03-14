<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Roadmap\Enums\IdeaStatus;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Models\IdeaComment;
use Modules\Roadmap\Services\IdeaService;
use Modules\Roadmap\Services\VotingService;

class IdeaController extends Controller
{
    public function index(Request $request)
    {
        $query = Idea::with('board', 'user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('board_id')) {
            $query->where('board_id', $request->board_id);
        }

        return view('roadmap::admin.ideas.index', [
            'ideas' => $query->paginate(30),
            'boards' => Board::ordered()->get(),
            'statuses' => IdeaStatus::cases(),
            'filters' => $request->only(['status', 'board_id']),
        ]);
    }

    public function show(Idea $idea)
    {
        $idea->load(['board', 'user', 'comments.user', 'votes']);

        return view('roadmap::admin.ideas.show', compact('idea'));
    }

    public function store(Request $request, Board $board, IdeaService $service)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
        ]);

        $service->create($board, $validated, auth()->id());

        return redirect()->back()->with('success', __('Idea submitted successfully.'));
    }

    public function updateStatus(Request $request, Idea $idea, IdeaService $service)
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', array_column(IdeaStatus::cases(), 'value')),
        ]);

        $service->updateStatus($idea, $validated['status']);

        return redirect()->back()->with('success', __('Idea status updated.'));
    }

    public function merge(Request $request, Idea $idea, IdeaService $service)
    {
        $validated = $request->validate([
            'target_id' => 'required|exists:ideas,id',
        ]);

        $target = Idea::findOrFail($validated['target_id']);
        $service->merge($idea, $target);

        return redirect()->back()->with('success', __('Ideas merged successfully.'));
    }

    public function destroy(Idea $idea)
    {
        $idea->delete();

        return redirect()->back()->with('success', __('Idea deleted.'));
    }

    public function toggleVote(Idea $idea, VotingService $service)
    {
        $voted = $service->toggle($idea, auth()->id());

        return response()->json([
            'voted' => $voted,
            'vote_count' => $idea->fresh()->vote_count,
        ]);
    }

    public function addOfficialComment(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'content' => 'required',
        ]);

        IdeaComment::create([
            'idea_id' => $idea->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'is_official' => true,
        ]);

        $idea->increment('comment_count');

        return redirect()->back()->with('success', __('Official comment added.'));
    }

    public function addComment(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'content' => 'required',
        ]);

        IdeaComment::create([
            'idea_id' => $idea->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'is_official' => false,
        ]);

        $idea->increment('comment_count');

        return redirect()->back()->with('success', __('Comment added.'));
    }
}
