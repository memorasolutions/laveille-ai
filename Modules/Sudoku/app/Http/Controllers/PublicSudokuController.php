<?php

declare(strict_types=1);

namespace Modules\Sudoku\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Modules\Sudoku\Models\SavedSudokuPreset;
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

    /**
     * #170 : Mes parties — auth uniquement. Liste les SavedSudokuPreset du user
     * avec lien pour reprendre la grille a son etat de sauvegarde.
     */
    public function mesParties(Request $request)
    {
        $presets = SavedSudokuPreset::query()
            ->forUser(Auth::id())
            ->with('puzzle')
            ->orderByDesc('last_saved_at')
            ->limit(50)
            ->get();

        return View::make('sudoku::mes-parties', compact('presets'));
    }
}
