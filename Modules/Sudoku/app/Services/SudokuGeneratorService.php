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
    protected const DIFFICULTY_RANGES = [
        'easy' => [43, 46],
        'medium' => [33, 36],
        'hard' => [28, 31],
        'expert' => [24, 27],
        'diabolical' => [20, 23],
    ];

    public function generate(string $difficulty): array
    {
        $startTime = microtime(true);

        $solution = $this->generateSolvedGrid();
        $cluesCount = random_int(...self::DIFFICULTY_RANGES[$difficulty]);
        $gridInit = $this->removeNumbers($solution, 81 - $cluesCount);

        return [
            'grid_init' => $gridInit,
            'solution' => $solution,
            'clues_count' => $cluesCount,
            'time_ms' => (int) ((microtime(true) - $startTime) * 1000),
        ];
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

    protected function removeNumbers(array $grid, int $count): array
    {
        $positions = range(0, 80);
        shuffle($positions);

        $gridCopy = $grid;
        $removed = 0;
        foreach ($positions as $pos) {
            if ($removed >= $count) break;
            $row = intdiv($pos, 9);
            $col = $pos % 9;
            $backup = $gridCopy[$row][$col];
            $gridCopy[$row][$col] = 0;

            $solutionCount = 0;
            $tempGrid = $gridCopy;
            self::solveCount($tempGrid, $solutionCount, 2);
            if ($solutionCount === 1) {
                $removed++;
            } else {
                $gridCopy[$row][$col] = $backup;
            }
        }

        return $gridCopy;
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
