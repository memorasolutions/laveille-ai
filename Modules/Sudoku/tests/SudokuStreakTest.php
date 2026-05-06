<?php

declare(strict_types=1);

use Modules\Sudoku\Models\SudokuStreak;

it('SudokuStreak fillable contient les bonnes colonnes', function () {
    $streak = new SudokuStreak();
    expect($streak->getFillable())->toContain('user_id', 'pseudo_hash', 'current_streak', 'longest_streak', 'last_played_date', 'total_completed');
});

it('SudokuStreak has casts last_played_date as date', function () {
    $streak = new SudokuStreak();
    expect($streak->getCasts())->toHaveKey('last_played_date');
    expect($streak->getCasts()['last_played_date'])->toBe('date');
});
