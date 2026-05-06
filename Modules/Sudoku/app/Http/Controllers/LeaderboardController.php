<?php

declare(strict_types=1);

namespace Modules\Sudoku\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Modules\Sudoku\Models\SudokuScore;
use Modules\Sudoku\Models\SudokuStreak;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $data = Cache::remember('sudoku.leaderboards.v1', now()->addMinutes(5), function () {
            $difficulties = ['easy', 'medium', 'hard', 'expert', 'diabolical'];
            $todayByDifficulty = [];

            foreach ($difficulties as $difficulty) {
                $todayByDifficulty[$difficulty] = SudokuScore::query()
                    ->where('is_published_in_leaderboard', true)
                    ->join('sudoku_puzzles', 'sudoku_scores.puzzle_id', '=', 'sudoku_puzzles.id')
                    ->where('sudoku_puzzles.difficulty', $difficulty)
                    ->whereDate('sudoku_puzzles.date', now()->toDateString())
                    ->orderByDesc('sudoku_scores.score')
                    ->orderBy('sudoku_scores.time_seconds')
                    ->select('sudoku_scores.*')
                    ->limit(20)
                    ->get();
            }

            $week = SudokuScore::query()
                ->where('is_published_in_leaderboard', true)
                ->join('sudoku_puzzles', 'sudoku_scores.puzzle_id', '=', 'sudoku_puzzles.id')
                ->whereDate('sudoku_puzzles.date', '>=', now()->subDays(7)->toDateString())
                ->orderByDesc('sudoku_scores.score')
                ->select('sudoku_scores.*')
                ->limit(20)
                ->get();

            $month = SudokuScore::query()
                ->where('is_published_in_leaderboard', true)
                ->join('sudoku_puzzles', 'sudoku_scores.puzzle_id', '=', 'sudoku_puzzles.id')
                ->whereDate('sudoku_puzzles.date', '>=', now()->subDays(30)->toDateString())
                ->orderByDesc('sudoku_scores.score')
                ->select('sudoku_scores.*')
                ->limit(20)
                ->get();

            $alltime = SudokuScore::published()
                ->orderByDesc('score')
                ->limit(50)
                ->get();

            $countries = SudokuScore::published()
                ->selectRaw('country, COUNT(*) as count')
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $streaks = SudokuStreak::orderByDesc('longest_streak')
                ->limit(20)
                ->get();

            return compact('todayByDifficulty', 'week', 'month', 'alltime', 'countries', 'streaks');
        });

        return View::make('sudoku::leaderboards', $data);
    }
}
