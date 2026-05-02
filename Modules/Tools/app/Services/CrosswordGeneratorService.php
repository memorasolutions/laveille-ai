<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use InvalidArgumentException;
use RuntimeException;

final class CrosswordGeneratorService
{
    public const MAX_GRID_SIZE = 30;
    public const MIN_WORD_LENGTH = 2;
    public const MAX_WORD_LENGTH = 30;

    public function generate(array $pairs, ?int $seed = null): array
    {
        $startTime = microtime(true);

        $this->validatePairs($pairs);

        $usedSeed = $seed !== null ? max(1, $seed) : mt_rand(1, PHP_INT_MAX - 1);
        mt_srand($usedSeed);

        $normalizedPairs = [];
        foreach ($pairs as $pair) {
            $normalizedPairs[] = [
                'clue' => trim($pair['clue']),
                'answer' => $this->normalizeAnswer($pair['answer']),
                'letters' => $this->splitLetters($this->normalizeAnswer($pair['answer'])),
            ];
        }

        shuffle($normalizedPairs);
        usort($normalizedPairs, fn ($a, $b) => count($b['letters']) <=> count($a['letters']));

        $grid = array_fill(0, self::MAX_GRID_SIZE, array_fill(0, self::MAX_GRID_SIZE, null));
        $placedWords = [];
        $unplaced = [];

        $first = $normalizedPairs[0];
        $center = (int) (self::MAX_GRID_SIZE / 2);
        $rowOffset = mt_rand(-3, 3);
        $colOffset = mt_rand(-3, 3);
        $startRow = max(2, min(self::MAX_GRID_SIZE - 3, $center + $rowOffset));
        $startCol = $center - (int) (count($first['letters']) / 2) + $colOffset;
        $startCol = max(2, min(self::MAX_GRID_SIZE - count($first['letters']) - 2, $startCol));
        $firstOrientation = mt_rand(0, 1) === 1 ? 'horizontal' : 'vertical';

        if ($firstOrientation === 'vertical') {
            $startRow = max(2, min(self::MAX_GRID_SIZE - count($first['letters']) - 2, $startRow));
            $startCol = max(2, min(self::MAX_GRID_SIZE - 3, $startCol));
        }

        if ($startCol < 0 || $startRow < 0
            || ($firstOrientation === 'horizontal' && $startCol + count($first['letters']) > self::MAX_GRID_SIZE)
            || ($firstOrientation === 'vertical' && $startRow + count($first['letters']) > self::MAX_GRID_SIZE)) {
            throw new RuntimeException('Premier mot trop long pour la grille.');
        }

        $this->placeWord($grid, $first['letters'], $startRow, $startCol, $firstOrientation);
        $placedWords[] = [
            'number' => 0,
            'orientation' => $firstOrientation,
            'row' => $startRow,
            'col' => $startCol,
            'answer' => $first['answer'],
            'clue' => $first['clue'],
            'length' => count($first['letters']),
        ];

        for ($i = 1; $i < count($normalizedPairs); $i++) {
            $pair = $normalizedPairs[$i];
            $candidates = $this->findCandidatePositions($grid, $pair['letters']);

            if (empty($candidates)) {
                $unplaced[] = ['clue' => $pair['clue'], 'answer' => $pair['answer']];
                continue;
            }

            shuffle($candidates);
            usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);
            $topPool = array_slice($candidates, 0, min(3, count($candidates)));
            $best = $topPool[mt_rand(0, count($topPool) - 1)];

            $this->placeWord($grid, $pair['letters'], $best['row'], $best['col'], $best['orientation']);
            $placedWords[] = [
                'number' => 0,
                'orientation' => $best['orientation'],
                'row' => $best['row'],
                'col' => $best['col'],
                'answer' => $pair['answer'],
                'clue' => $pair['clue'],
                'length' => count($pair['letters']),
            ];
        }

        if (count($placedWords) === 0) {
            return [
                'success' => false,
                'grid' => null,
                'words' => [],
                'unplaced' => array_map(fn ($p) => ['clue' => $p['clue'], 'answer' => $p['answer']], $normalizedPairs),
                'stats' => [
                    'placed_count' => 0,
                    'total_count' => count($pairs),
                    'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
                    'compactness' => 0.0,
                    'seed' => $usedSeed,
                ],
            ];
        }

        $trimmed = $this->trimGrid($grid, $placedWords);
        $this->numberWords($trimmed['cells'], $placedWords);

        $nonNullCells = 0;
        foreach ($trimmed['cells'] as $row) {
            foreach ($row as $cell) {
                if ($cell !== null) {
                    $nonNullCells++;
                }
            }
        }
        $totalCells = $trimmed['rows'] * $trimmed['cols'];
        $compactness = $totalCells > 0 ? round($nonNullCells / $totalCells, 3) : 0.0;

        return [
            'success' => true,
            'grid' => $trimmed,
            'words' => $placedWords,
            'unplaced' => $unplaced,
            'stats' => [
                'placed_count' => count($placedWords),
                'total_count' => count($pairs),
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'compactness' => $compactness,
                'seed' => $usedSeed,
            ],
        ];
    }

    private function normalizeAnswer(string $answer): string
    {
        return mb_strtoupper(trim($answer));
    }

    /**
     * Split UTF-8 string en array de "lettres" (1 char visible = 1 entrée).
     * Gère accents é è à ê â ç ï ô ù û correctement.
     */
    private function splitLetters(string $word): array
    {
        return mb_str_split($word);
    }

    private function validatePairs(array $pairs): void
    {
        $count = count($pairs);
        if ($count < 2) {
            throw new InvalidArgumentException('Au moins 2 paires sont requises pour générer une grille.');
        }
        if ($count > 50) {
            throw new InvalidArgumentException('Maximum 50 paires autorisées.');
        }

        foreach ($pairs as $index => $pair) {
            $position = $index + 1;
            if (! is_array($pair) || ! isset($pair['clue']) || ! isset($pair['answer'])) {
                throw new InvalidArgumentException("Paire #{$position} : champs 'clue' et 'answer' obligatoires.");
            }
            if (! is_string($pair['clue']) || ! is_string($pair['answer'])) {
                throw new InvalidArgumentException("Paire #{$position} : clue et answer doivent être des chaînes.");
            }

            $clue = trim($pair['clue']);
            $answer = trim($pair['answer']);

            if ($clue === '') {
                throw new InvalidArgumentException("Paire #{$position} : l'indice est obligatoire.");
            }
            if (mb_strlen($clue) > 250) {
                throw new InvalidArgumentException("Paire #{$position} : l'indice ne peut dépasser 250 caractères.");
            }
            $answerLen = mb_strlen($answer);
            if ($answerLen < self::MIN_WORD_LENGTH) {
                throw new InvalidArgumentException("Paire #{$position} : la réponse doit contenir au moins ".self::MIN_WORD_LENGTH.' lettres.');
            }
            if ($answerLen > self::MAX_WORD_LENGTH) {
                throw new InvalidArgumentException("Paire #{$position} : la réponse ne peut dépasser ".self::MAX_WORD_LENGTH.' caractères.');
            }
            if (! preg_match('/^[a-zA-ZàâäéèêëïîôöùûüÿçÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ]+$/u', $answer)) {
                throw new InvalidArgumentException("Paire #{$position} : la réponse ne peut contenir que des lettres (accents permis).");
            }
        }
    }

    private function canPlaceWord(array $grid, array $letters, int $row, int $col, string $orientation): bool
    {
        $length = count($letters);
        $maxSize = self::MAX_GRID_SIZE;

        if ($orientation === 'horizontal') {
            if ($col < 0 || $col + $length > $maxSize || $row < 0 || $row >= $maxSize) {
                return false;
            }
            $prevCol = $col - 1;
            $nextCol = $col + $length;
            if ($prevCol >= 0 && $grid[$row][$prevCol] !== null) {
                return false;
            }
            if ($nextCol < $maxSize && $grid[$row][$nextCol] !== null) {
                return false;
            }

            $hasIntersection = false;
            for ($c = 0; $c < $length; $c++) {
                $currentCol = $col + $c;
                $cell = $grid[$row][$currentCol] ?? null;
                if ($cell !== null) {
                    if ($cell['letter'] !== $letters[$c]) {
                        return false;
                    }
                    $hasIntersection = true;
                    continue;
                }

                $aboveRow = $row - 1;
                $belowRow = $row + 1;
                if ($aboveRow >= 0 && $grid[$aboveRow][$currentCol] !== null) {
                    return false;
                }
                if ($belowRow < $maxSize && $grid[$belowRow][$currentCol] !== null) {
                    return false;
                }
            }

            return $hasIntersection;
        }

        if ($orientation === 'vertical') {
            if ($row < 0 || $row + $length > $maxSize || $col < 0 || $col >= $maxSize) {
                return false;
            }
            $prevRow = $row - 1;
            $nextRow = $row + $length;
            if ($prevRow >= 0 && $grid[$prevRow][$col] !== null) {
                return false;
            }
            if ($nextRow < $maxSize && $grid[$nextRow][$col] !== null) {
                return false;
            }

            $hasIntersection = false;
            for ($r = 0; $r < $length; $r++) {
                $currentRow = $row + $r;
                $cell = $grid[$currentRow][$col] ?? null;
                if ($cell !== null) {
                    if ($cell['letter'] !== $letters[$r]) {
                        return false;
                    }
                    $hasIntersection = true;
                    continue;
                }

                $leftCol = $col - 1;
                $rightCol = $col + 1;
                if ($leftCol >= 0 && $grid[$currentRow][$leftCol] !== null) {
                    return false;
                }
                if ($rightCol < $maxSize && $grid[$currentRow][$rightCol] !== null) {
                    return false;
                }
            }

            return $hasIntersection;
        }

        return false;
    }

    private function placeWord(array &$grid, array $letters, int $row, int $col, string $orientation): void
    {
        $length = count($letters);
        if ($orientation === 'horizontal') {
            for ($c = 0; $c < $length; $c++) {
                $grid[$row][$col + $c] = ['letter' => $letters[$c], 'number' => null];
            }
            return;
        }
        for ($r = 0; $r < $length; $r++) {
            $grid[$row + $r][$col] = ['letter' => $letters[$r], 'number' => null];
        }
    }

    private function findCandidatePositions(array $grid, array $letters): array
    {
        $candidates = [];
        $length = count($letters);
        $maxSize = self::MAX_GRID_SIZE;
        $center = (int) ($maxSize / 2);

        for ($r = 0; $r < $maxSize; $r++) {
            for ($c = 0; $c < $maxSize; $c++) {
                $cell = $grid[$r][$c] ?? null;
                if ($cell === null) {
                    continue;
                }
                $cellLetter = $cell['letter'];
                for ($i = 0; $i < $length; $i++) {
                    if ($letters[$i] !== $cellLetter) {
                        continue;
                    }
                    // horizontal
                    $hRow = $r;
                    $hCol = $c - $i;
                    if ($this->canPlaceWord($grid, $letters, $hRow, $hCol, 'horizontal')) {
                        $intersections = $this->countIntersections($grid, $letters, $hRow, $hCol, 'horizontal');
                        $dist = abs($hRow - $center) + abs($hCol - $center);
                        $candidates[] = [
                            'row' => $hRow,
                            'col' => $hCol,
                            'orientation' => 'horizontal',
                            'intersections' => $intersections,
                            'score' => ($intersections * 10) + max(0, 50 - $dist),
                        ];
                    }
                    // vertical
                    $vRow = $r - $i;
                    $vCol = $c;
                    if ($this->canPlaceWord($grid, $letters, $vRow, $vCol, 'vertical')) {
                        $intersections = $this->countIntersections($grid, $letters, $vRow, $vCol, 'vertical');
                        $dist = abs($vRow - $center) + abs($vCol - $center);
                        $candidates[] = [
                            'row' => $vRow,
                            'col' => $vCol,
                            'orientation' => 'vertical',
                            'intersections' => $intersections,
                            'score' => ($intersections * 10) + max(0, 50 - $dist),
                        ];
                    }
                }
            }
        }

        return $candidates;
    }

    private function countIntersections(array $grid, array $letters, int $row, int $col, string $orientation): int
    {
        $count = 0;
        $length = count($letters);
        if ($orientation === 'horizontal') {
            for ($c = 0; $c < $length; $c++) {
                if ($grid[$row][$col + $c] !== null) {
                    $count++;
                }
            }
            return $count;
        }
        for ($r = 0; $r < $length; $r++) {
            if ($grid[$row + $r][$col] !== null) {
                $count++;
            }
        }
        return $count;
    }

    private function trimGrid(array $grid, array &$placedWords): array
    {
        $maxSize = self::MAX_GRID_SIZE;
        $minRow = $maxSize;
        $maxRow = -1;
        $minCol = $maxSize;
        $maxCol = -1;

        for ($r = 0; $r < $maxSize; $r++) {
            for ($c = 0; $c < $maxSize; $c++) {
                if ($grid[$r][$c] !== null) {
                    if ($r < $minRow) $minRow = $r;
                    if ($r > $maxRow) $maxRow = $r;
                    if ($c < $minCol) $minCol = $c;
                    if ($c > $maxCol) $maxCol = $c;
                }
            }
        }

        if ($minRow > $maxRow) {
            return ['rows' => 0, 'cols' => 0, 'cells' => []];
        }

        $newRows = $maxRow - $minRow + 1;
        $newCols = $maxCol - $minCol + 1;
        $cells = [];
        for ($r = 0; $r < $newRows; $r++) {
            for ($c = 0; $c < $newCols; $c++) {
                $cells[$r][$c] = $grid[$minRow + $r][$minCol + $c];
            }
        }

        foreach ($placedWords as &$word) {
            $word['row'] -= $minRow;
            $word['col'] -= $minCol;
        }
        unset($word);

        return ['rows' => $newRows, 'cols' => $newCols, 'cells' => $cells];
    }

    /**
     * Numérote les mots dans l'ordre de lecture (haut→bas, gauche→droite).
     * 2 mots commençant à la même cellule (1 horizontal + 1 vertical) partagent le même numéro.
     */
    private function numberWords(array &$cells, array &$placedWords): void
    {
        if (count($cells) === 0) {
            return;
        }

        // Trier mots par position pour numérotation cohérente
        usort($placedWords, function ($a, $b) {
            if ($a['row'] !== $b['row']) {
                return $a['row'] <=> $b['row'];
            }
            return $a['col'] <=> $b['col'];
        });

        $cellNumberMap = []; // "row,col" => number
        $nextNumber = 1;

        foreach ($placedWords as &$word) {
            $key = $word['row'].','.$word['col'];
            if (isset($cellNumberMap[$key])) {
                $word['number'] = $cellNumberMap[$key];
                continue;
            }
            $cellNumberMap[$key] = $nextNumber;
            $word['number'] = $nextNumber;

            // Placer le numéro dans la cellule
            if (isset($cells[$word['row']][$word['col']]) && $cells[$word['row']][$word['col']] !== null) {
                $cells[$word['row']][$word['col']]['number'] = $nextNumber;
            }

            $nextNumber++;
        }
        unset($word);
    }
}
