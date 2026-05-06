<?php

declare(strict_types=1);

namespace Modules\Sudoku\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SudokuPuzzle extends Model
{
    use HasFactory;

    protected $table = 'sudoku_puzzles';

    protected $fillable = [
        'difficulty',
        'date',
        'grid_init',
        'solution',
        'clues_count',
        'generation_time_ms',
    ];

    protected $casts = [
        'grid_init' => 'array',
        'solution' => 'array',
        'date' => 'date',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(SudokuScore::class, 'puzzle_id');
    }

    public function scopeOfDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->toDateString());
    }

    public function getDifficultyLabel(): string
    {
        return [
            'easy' => 'Facile',
            'medium' => 'Moyen',
            'hard' => 'Difficile',
            'expert' => 'Expert',
            'diabolical' => 'Diabolique',
        ][$this->difficulty] ?? $this->difficulty;
    }

    public function getDifficultyColor(): string
    {
        return [
            'easy' => '#10B981',
            'medium' => '#0B7285',
            'hard' => '#7C3AED',
            'expert' => '#C2410C',
            'diabolical' => '#1f2937',
        ][$this->difficulty] ?? '#6B7280';
    }
}
