<?php

declare(strict_types=1);

namespace Modules\Sudoku\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Modules\Sudoku\Models\SudokuPuzzle;
use Modules\Sudoku\Models\SudokuScore;
use Modules\Sudoku\Models\SudokuStreak;

class ScoreApiController extends Controller
{
    private const MULTIPLIERS = [
        'easy' => 1, 'medium' => 2, 'hard' => 3, 'expert' => 4, 'diabolical' => 5,
    ];

    private const MIN_TIMES = [
        'easy' => 60, 'medium' => 120, 'hard' => 180, 'expert' => 240, 'diabolical' => 300,
    ];

    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'puzzle_id' => 'required|integer|exists:sudoku_puzzles,id',
            'pseudo' => 'required|string|max:30',
            'time_seconds' => 'required|integer|min:30',
            'hints_used' => 'required|integer|min:0',
            'errors_count' => 'required|integer|min:0',
            'grid_complete' => 'required|array|size:9',
            'grid_complete.*' => 'array|size:9',
            'grid_complete.*.*' => 'integer|min:1|max:9',
        ]);

        $puzzle = SudokuPuzzle::findOrFail($data['puzzle_id']);

        // Verif solution cellule par cellule
        foreach ($data['grid_complete'] as $i => $row) {
            foreach ($row as $j => $value) {
                if ((int) $value !== (int) $puzzle->solution[$i][$j]) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Solution incorrecte ('.($i + 1).','.($j + 1).')',
                    ], 422);
                }
            }
        }

        $multiplier = self::MULTIPLIERS[$puzzle->difficulty];
        $minTime = self::MIN_TIMES[$puzzle->difficulty];

        $scoreValue = max(0,
            10000
            - ($data['time_seconds'] * $multiplier)
            - ($data['hints_used'] * 500)
            - ($data['errors_count'] * 200)
        );

        $isPublished = $data['time_seconds'] >= $minTime;
        $ipHash = hash('sha256', $request->ip().date('Y-m-d'));

        // Anti-spam : 1 score validateur par puzzle + ip_hash sur 60s
        $lockKey = "sudoku.score.{$data['puzzle_id']}.{$ipHash}";
        $lock = Cache::lock($lockKey, 60);
        if (! $lock->get()) {
            return response()->json(['success' => false, 'error' => 'Trop de tentatives'], 429);
        }

        try {
            $score = SudokuScore::create([
                'puzzle_id' => $data['puzzle_id'],
                'user_id' => $request->user()?->id,
                'pseudo' => $data['pseudo'],
                'ip_hash' => $ipHash,
                'country' => $request->header('cf-ipcountry') ?: null,
                'time_seconds' => $data['time_seconds'],
                'hints_used' => $data['hints_used'],
                'errors_count' => $data['errors_count'],
                'score' => $scoreValue,
                'completed_at' => now(),
                'is_published_in_leaderboard' => $isPublished,
            ]);

            // Streak
            if ($request->user()) {
                $streak = SudokuStreak::firstOrCreate(
                    ['user_id' => $request->user()->id],
                    ['pseudo_hash' => hash('sha256', (string) $request->user()->id), 'current_streak' => 0, 'longest_streak' => 0, 'total_completed' => 0]
                );
                $streak->recordCompletion();
            }

            // Rangs
            $rankToday = SudokuScore::query()
                ->where('is_published_in_leaderboard', true)
                ->where('puzzle_id', $data['puzzle_id'])
                ->where('score', '>', $scoreValue)
                ->count() + 1;

            $rankWeek = SudokuScore::query()
                ->where('is_published_in_leaderboard', true)
                ->join('sudoku_puzzles', 'sudoku_scores.puzzle_id', '=', 'sudoku_puzzles.id')
                ->whereDate('sudoku_puzzles.date', '>=', now()->subDays(7)->toDateString())
                ->where('sudoku_puzzles.difficulty', $puzzle->difficulty)
                ->where('sudoku_scores.score', '>', $scoreValue)
                ->count() + 1;

            // Invalidation cache leaderboards
            Cache::forget('sudoku.leaderboards.v1');

            return response()->json([
                'success' => true,
                'score' => $scoreValue,
                'rank_today' => $rankToday,
                'rank_week' => $rankWeek,
                'is_published' => $isPublished,
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    public function leaderboards(Request $request): JsonResponse
    {
        $data = Cache::remember('sudoku.leaderboards.v1', now()->addMinutes(5), function () {
            return ['cached_at' => now()->toIso8601String()];
        });

        return response()->json($data);
    }
}
