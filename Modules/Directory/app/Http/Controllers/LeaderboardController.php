<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(): View
    {
        $topAllTime = User::where('reputation_points', '>', 0)
            ->orderByDesc('reputation_points')
            ->limit(10)
            ->get(['id', 'name', 'reputation_points', 'trust_level', 'streak_days']);

        // Leaderboard mensuel (points gagnés ce mois-ci)
        $topMonthly = collect();
        if (Schema::hasTable('reputation_logs')) {
            $topMonthly = DB::table('reputation_logs')
                ->join('users', 'users.id', '=', 'reputation_logs.user_id')
                ->where('reputation_logs.created_at', '>=', now()->startOfMonth())
                ->groupBy('users.id', 'users.name', 'users.trust_level', 'users.streak_days')
                ->selectRaw('users.id, users.name, users.trust_level, users.streak_days, SUM(reputation_logs.points) as monthly_points')
                ->orderByDesc('monthly_points')
                ->limit(10)
                ->get();
        }

        return view('directory::public.leaderboard', compact('topAllTime', 'topMonthly'));
    }
}
