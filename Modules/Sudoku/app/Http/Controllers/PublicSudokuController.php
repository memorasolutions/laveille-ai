<?php

declare(strict_types=1);

namespace Modules\Sudoku\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Modules\Sudoku\Models\SudokuPuzzle;
use Modules\Sudoku\Services\SudokuGeneratorService;

class PublicSudokuController extends Controller
{
    public function play(Request $request)
    {
        $difficulties = ['easy', 'medium', 'hard', 'expert', 'diabolical'];
        $puzzles = [];

        foreach ($difficulties as $difficulty) {
            $puzzle = SudokuPuzzle::today()->ofDifficulty($difficulty)->first();
            if (! $puzzle) {
                $puzzle = app(SudokuGeneratorService::class)->generateForToday($difficulty);
            }
            $puzzles[$difficulty] = $puzzle;
        }

        return View::make('sudoku::play', compact('puzzles'));
    }

    public function showDate(Request $request, string $date)
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(404);
        }

        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        $today = new \DateTimeImmutable('today');
        if ($dateObj >= $today->modify('+1 day')) {
            abort(404);
        }

        $difficulties = ['easy', 'medium', 'hard', 'expert', 'diabolical'];
        $puzzles = [];

        foreach ($difficulties as $difficulty) {
            $puzzle = SudokuPuzzle::whereDate('date', $date)
                ->ofDifficulty($difficulty)
                ->first();
            if (! $puzzle) {
                abort(404);
            }
            $puzzles[$difficulty] = $puzzle;
        }

        return View::make('sudoku::play', compact('puzzles', 'date'));
    }

    public function archive(Request $request)
    {
        $days = SudokuPuzzle::selectRaw('date, COUNT(*) as puzzle_count')
            ->whereDate('date', '>=', now()->subDays(30)->startOfDay())
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        return View::make('sudoku::archive', compact('days'));
    }
}
