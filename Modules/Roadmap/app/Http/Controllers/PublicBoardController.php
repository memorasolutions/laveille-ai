<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Roadmap\Enums\IdeaStatus;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Models\IdeaComment;
use Modules\Roadmap\Services\IdeaService;
use Modules\Roadmap\Services\VotingService;

class PublicBoardController extends Controller
{
    public function index()
    {
        $boards = Board::where('is_public', true)
            ->ordered()
            ->withCount('ideas')
            ->get();

        return view('roadmap::public.boards.index', compact('boards'));
    }

    public function show(Board $board, Request $request)
    {
        abort_if(! $board->is_public, 404);

        $query = $board->ideas()
            ->with('user')
            ->withCount(['comments' => fn ($q) => $q->where('is_internal', false)]);

        if ($request->filled('status')) {
            $query->byStatus(IdeaStatus::from($request->status));
        }

        $ideas = $query->latest('vote_count')->paginate(20);

        return view('roadmap::public.boards.show', [
            'board' => $board,
            'ideas' => $ideas,
            'statuses' => IdeaStatus::cases(),
        ]);
    }

    public function storeIdea(Request $request, Board $board, IdeaService $service)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'category' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:50',
        ]);

        $service->create($board, $validated, auth()->id());

        $message = ($request->input('source') === 'glossaire')
            ? __('Merci ! Votre proposition de terme a été soumise. La communauté pourra voter dessus.')
            : __('Idea submitted successfully.');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function vote(Idea $idea, VotingService $service)
    {
        $voted = $service->toggle($idea, auth()->id());

        return response()->json([
            'voted' => $voted,
            'vote_count' => $idea->fresh()->vote_count,
        ]);
    }

    public function comment(Request $request, Idea $idea)
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

    public function kanban(Board $board)
    {
        abort_if(! $board->is_public, 404);

        $columns = collect(IdeaStatus::cases())->mapWithKeys(fn (IdeaStatus $s) => [
            $s->value => $board->ideas()
                ->where('status', $s)
                ->with('user')
                ->orderByDesc('vote_count')
                ->get(),
        ]);

        return view('roadmap::public.boards.kanban', [
            'board' => $board,
            'columns' => $columns,
            'statuses' => IdeaStatus::cases(),
        ]);
    }
}
