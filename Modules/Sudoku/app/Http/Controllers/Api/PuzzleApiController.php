<?php

declare(strict_types=1);

namespace Modules\Sudoku\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sudoku\Models\SudokuPuzzle;
use Modules\Sudoku\Services\SudokuGeneratorService;

class PuzzleApiController extends Controller
{
    private const ALLOWED = ['easy', 'medium', 'hard', 'expert', 'diabolical'];

    public function today(Request $request, string $difficulty): JsonResponse
    {
        abort_unless(in_array($difficulty, self::ALLOWED, true), 400);

        $puzzle = SudokuPuzzle::today()->ofDifficulty($difficulty)->first();
        if (! $puzzle) {
            $puzzle = app(SudokuGeneratorService::class)->generateForToday($difficulty);
        }

        return response()->json([
            'puzzle_id' => $puzzle->id,
            'grid_init' => $puzzle->grid_init,
            'clues_count' => $puzzle->clues_count,
            'difficulty' => $puzzle->difficulty,
            'difficulty_label' => $puzzle->getDifficultyLabel(),
            'difficulty_color' => $puzzle->getDifficultyColor(),
            'date' => $puzzle->date->toDateString(),
        ]);
    }

    public function byDate(Request $request, string $date, string $difficulty): JsonResponse
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(400);
        }
        abort_unless(in_array($difficulty, self::ALLOWED, true), 400);

        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($dateObj > new \DateTimeImmutable('today')) {
            abort(404);
        }

        $puzzle = SudokuPuzzle::whereDate('date', $date)->ofDifficulty($difficulty)->first();
        abort_unless($puzzle, 404);

        return response()->json([
            'puzzle_id' => $puzzle->id,
            'grid_init' => $puzzle->grid_init,
            'clues_count' => $puzzle->clues_count,
            'difficulty' => $puzzle->difficulty,
            'difficulty_label' => $puzzle->getDifficultyLabel(),
            'difficulty_color' => $puzzle->getDifficultyColor(),
            'date' => $puzzle->date->toDateString(),
        ]);
    }
}
