<?php

declare(strict_types=1);

namespace Modules\Sudoku\Services;

/**
 * Génération Sudoku 100% PHP serveur — aucune dépendance externe.
 * Algorithme : backtracking + remplissage diagonale + retrait avec garantie unicité solution.
 *
 * Cibles perf (PHP 8.4) :
 *  - easy/medium  : ~50-200 ms
 *  - hard/expert  : ~200-500 ms
 *  - diabolical   : ~500-1500 ms (plus de retraits = plus de validations)
 */
class SudokuGeneratorService
{
    /**
     * #232 (2026-06-08) : nombre de GIVENS (indices conservés) par niveau,
     * désormais DISTINCTS et MONOTONES (best practices juin 2026, fourchettes
     * NYT/Conceptis/Sudoku Coach). Le retrait multi-passes (digHoles) atteint
     * ces cibles de façon fiable et stocke le compte RÉEL (et non la cible).
     *
     * Easy : 40 (fourchette facile 36-50, scanning + singles)
     * Medium : 34 (paires nues, locked candidates)
     * Hard : 30 (pointing pairs, tuples)
     * Expert : 26 (techniques avancées, proche < 25)
     * Diabolical : 22 (retrait maximal, proche du plancher pratique du greedy)
     *
     * NB : le clue-count reste un proxy de difficulté ; un classement par
     * technique de résolution (Option B) est l'amélioration recommandée ensuite.
     */
    protected const DIFFICULTY_GIVENS = [
        'easy' => 40,
        'medium' => 34,
        'hard' => 30,
        'expert' => 26,
        'diabolical' => 22,
    ];

    public function generate(string $difficulty): array
    {
        $startTime = microtime(true);

        $solution = $this->generateSolvedGrid();
        $targetGivens = self::DIFFICULTY_GIVENS[$difficulty];
        [$gridInit, $actualGivens] = $this->digHoles($solution, $targetGivens);

        return [
            'grid_init' => $gridInit,
            'solution' => $solution,
            'clues_count' => $actualGivens, // compte RÉEL d'indices conservés
            'time_ms' => (int) ((microtime(true) - $startTime) * 1000),
        ];
    }

    /**
     * Genere un puzzle pour une difficulte aujourd'hui (date America/Toronto)
     * et le persiste en DB. Idempotent via unique(date, difficulty).
     */
    public function generateForToday(string $difficulty): \Modules\Sudoku\Models\SudokuPuzzle
    {
        $date = now('America/Toronto')->toDateString();
        $existing = \Modules\Sudoku\Models\SudokuPuzzle::where('date', $date)
            ->where('difficulty', $difficulty)
            ->first();
        if ($existing) {
            return $existing;
        }

        $data = $this->generate($difficulty);

        return \Modules\Sudoku\Models\SudokuPuzzle::create([
            'difficulty' => $difficulty,
            'date' => $date,
            'grid_init' => $data['grid_init'],
            'solution' => $data['solution'],
            'clues_count' => $data['clues_count'],
            'generation_time_ms' => $data['time_ms'],
        ]);
    }

    protected function generateSolvedGrid(): array
    {
        $grid = array_fill(0, 9, array_fill(0, 9, 0));
        $this->fillDiagonalBoxes($grid);
        $this->solveGrid($grid);
        return $grid;
    }

    protected function fillDiagonalBoxes(array &$grid): void
    {
        for ($box = 0; $box < 9; $box += 3) {
            $numbers = range(1, 9);
            shuffle($numbers);
            $index = 0;
            for ($i = 0; $i < 3; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $grid[$box + $i][$box + $j] = $numbers[$index++];
                }
            }
        }
    }

    protected function solveGrid(array &$grid): bool
    {
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($grid[$row][$col] === 0) {
                    $numbers = range(1, 9);
                    shuffle($numbers);
                    foreach ($numbers as $num) {
                        if (self::isValidStatic($grid, $row, $col, $num)) {
                            $grid[$row][$col] = $num;
                            if ($this->solveGrid($grid)) {
                                return true;
                            }
                            $grid[$row][$col] = 0;
                        }
                    }
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Creuse la grille en RETRAIT MULTI-PASSES jusqu'à atteindre la cible de givens,
     * en garantissant l'unicité de la solution après chaque retrait.
     * Le multi-passes permet d'atteindre des comptes bas (cases non retirables en
     * début de passe le deviennent une fois d'autres cases vidées) — corrige le
     * blocage du greedy 1-passe vers ~24 givens. Retourne [grille, givens réels].
     */
    protected function digHoles(array $grid, int $targetGivens): array
    {
        $givens = 81; // grille initialement pleine
        $maxPasses = 6; // borne le nombre de passes
        $deadline = microtime(true) + 12.0; // garde-fou temps (anti-timeout cron)
        $positions = range(0, 80);

        for ($pass = 0; $pass < $maxPasses; $pass++) {
            if ($givens <= $targetGivens) break;

            $removedInPass = 0;
            shuffle($positions); // ordre aléatoire à chaque passe

            foreach ($positions as $pos) {
                if ($givens <= $targetGivens) break;
                if (microtime(true) > $deadline) break 2; // budget temps dépassé → on garde l'état courant

                $row = intdiv($pos, 9);
                $col = $pos % 9;
                if ($grid[$row][$col] === 0) continue; // déjà vide

                $backup = $grid[$row][$col];
                $grid[$row][$col] = 0;

                $count = 0;
                $gridCopy = $grid;
                self::solveCount($gridCopy, $count, 2);

                if ($count === 1) {
                    $givens--;
                    $removedInPass++;
                } else {
                    $grid[$row][$col] = $backup; // retrait annulé (solution non unique)
                }
            }

            if ($removedInPass === 0) break; // plancher local atteint
        }

        return [$grid, $givens];
    }

    /**
     * Compte le nombre de solutions d'une grille (max $limit pour court-circuit).
     */
    public static function solveCount(array $grid, int &$count, int $limit = 2): void
    {
        if ($count >= $limit) return;

        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($grid[$row][$col] === 0) {
                    for ($num = 1; $num <= 9; $num++) {
                        if (self::isValidStatic($grid, $row, $col, $num)) {
                            $grid[$row][$col] = $num;
                            self::solveCount($grid, $count, $limit);
                            $grid[$row][$col] = 0;
                            if ($count >= $limit) return;
                        }
                    }
                    return;
                }
            }
        }
        $count++;
    }

    public static function isValidStatic(array $grid, int $row, int $col, int $num): bool
    {
        for ($x = 0; $x < 9; $x++) {
            if ($grid[$row][$x] === $num || $grid[$x][$col] === $num) {
                return false;
            }
        }
        $startRow = $row - $row % 3;
        $startCol = $col - $col % 3;
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($grid[$i + $startRow][$j + $startCol] === $num) {
                    return false;
                }
            }
        }
        return true;
    }
}
