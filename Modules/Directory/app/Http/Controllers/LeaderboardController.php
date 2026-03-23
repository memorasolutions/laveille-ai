<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(): View
    {
        $topAllTime = User::where('reputation_points', '>', 0)
            ->orderByDesc('reputation_points')
            ->limit(10)
            ->get(['id', 'name', 'reputation_points', 'trust_level']);

        return view('directory::public.leaderboard', compact('topAllTime'));
    }
}
