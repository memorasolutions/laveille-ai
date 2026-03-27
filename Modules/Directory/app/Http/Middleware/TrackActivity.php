<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $today = now()->toDateString();

            // Ne mettre à jour qu'une fois par requête et seulement si la date a changé
            if ($user->streak_last_date !== $today) {
                $yesterday = now()->subDay()->toDateString();

                $streakDays = ($user->streak_last_date === $yesterday)
                    ? $user->streak_days + 1
                    : 1;

                DB::table('users')->where('id', $user->id)->update([
                    'last_active_at' => now(),
                    'streak_days' => $streakDays,
                    'streak_last_date' => $today,
                ]);

                // Badges streak
                if ($streakDays === 7 || $streakDays === 30) {
                    $badgeKey = $streakDays === 7 ? 'streak_7' : 'streak_30';
                    DB::table('user_badges')->insertOrIgnore([
                        'user_id' => $user->id,
                        'badge_key' => $badgeKey,
                        'earned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Points bonus streak
                    if (class_exists(\Modules\Directory\Services\ReputationService::class)) {
                        $points = $streakDays === 7 ? 10 : 25;
                        DB::table('users')->where('id', $user->id)->increment('reputation_points', $points);
                        DB::table('reputation_logs')->insert([
                            'user_id' => $user->id,
                            'points' => $points,
                            'reason' => "streak_{$streakDays}",
                            'created_at' => now(),
                        ]);
                    }
                }
            }
        }

        return $next($request);
    }
}
