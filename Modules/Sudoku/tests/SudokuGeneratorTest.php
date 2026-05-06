<?php

declare(strict_types=1);

use Modules\Sudoku\Services\SudokuGeneratorService;

it('genere une grille resolue valide 9x9 sans 0', function () {
    $service = new SudokuGeneratorService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateSolvedGrid');
    $method->setAccessible(true);
    $grid = $method->invoke($service);

    expect($grid)->toBeArray()->toHaveCount(9);
    foreach ($grid as $row) {
        expect($row)->toBeArray()->toHaveCount(9);
        foreach ($row as $v) {
            expect($v)->toBeInt()->toBeGreaterThan(0)->toBeLessThan(10);
        }
    }

    // Chaque ligne contient 1-9 sans doublon
    foreach ($grid as $row) {
        expect(array_unique($row))->toHaveCount(9);
    }
});

it('genere un puzzle facile avec 43-46 indices', function () {
    $service = new SudokuGeneratorService();
    $data = $service->generate('easy');

    expect($data)->toHaveKeys(['grid_init', 'solution', 'clues_count', 'time_ms']);
    expect($data['clues_count'])->toBeGreaterThanOrEqual(43)->toBeLessThanOrEqual(46);

    // Compter les cellules non vides
    $nonZero = 0;
    foreach ($data['grid_init'] as $row) {
        foreach ($row as $v) if ($v !== 0) $nonZero++;
    }
    expect($nonZero)->toBe($data['clues_count']);
});

it('SudokuGeneratorService::isValidStatic detecte conflits ligne/col/box', function () {
    $grid = array_fill(0, 9, array_fill(0, 9, 0));
    $grid[0][0] = 5;

    expect(SudokuGeneratorService::isValidStatic($grid, 0, 5, 5))->toBeFalse(); // ligne
    expect(SudokuGeneratorService::isValidStatic($grid, 5, 0, 5))->toBeFalse(); // colonne
    expect(SudokuGeneratorService::isValidStatic($grid, 1, 1, 5))->toBeFalse(); // box
    expect(SudokuGeneratorService::isValidStatic($grid, 4, 4, 5))->toBeTrue();  // ailleurs
});
