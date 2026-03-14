<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Roadmap\Enums\IdeaStatus;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Models\Vote;

class RoadmapAnalyticsController extends Controller
{
    public function index()
    {
        $totalIdeas = Idea::count();

        $ideasByStatus = collect(IdeaStatus::cases())->mapWithKeys(
            fn (IdeaStatus $s) => [$s->value => Idea::where('status', $s)->count()]
        );

        $totalVotes = Vote::count();
        $topIdeas = Idea::with('board')->orderByDesc('vote_count')->limit(10)->get();
        $recentIdeas = Idea::with('board', 'user')->latest()->limit(10)->get();
        $totalBoards = Board::count();

        return view('roadmap::admin.analytics.index', compact(
            'totalIdeas', 'ideasByStatus', 'totalVotes',
            'topIdeas', 'recentIdeas', 'totalBoards'
        ));
    }
}
