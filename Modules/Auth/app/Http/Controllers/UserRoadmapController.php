<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Roadmap\Models\Board;

final class UserRoadmapController extends Controller
{
    public function ideas(): View
    {
        return $this->showBoard('idees');
    }

    public function bugs(): View
    {
        return $this->showBoard('bugs');
    }

    private function showBoard(string $slug): View
    {
        $board = Board::where('slug', $slug)
            ->with([
                'ideas' => fn ($query) => $query
                    ->withCount('votes')
                    ->with('user')
                    ->orderByDesc('votes_count'),
            ])
            ->firstOrFail();

        return view('auth::roadmap.board', compact('board'));
    }
}
