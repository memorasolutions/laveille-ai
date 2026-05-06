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
     * #170 + #171 : Mes parties Sudoku redirige vers le dashboard global de
     * sauvegarde unifie /user/saved?type=sudoku (UserSavedController agrege
     * tous les SavedXxxPreset avec filtre par type).
     * #182 fix : la vue mes-parties.blade.php n'existait pas -> 500. Redirect
     * propre coherent avec le pattern Memora.
     */
    public function mesParties(Request $request)
    {
        return redirect()->to('/user/saved?type=sudoku');
    }
}
