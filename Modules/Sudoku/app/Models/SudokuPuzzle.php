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

    /**
     * #207 : couleurs assombries WCAG 2.2 AAA (>=7:1 sur fond blanc).
     * Coherent avec leaderboards CSS classes lb-* et play pills.
     */
    public function getDifficultyColor(): string
    {
        return [
            'easy' => '#065F46',       // emerald-800, 8.5:1
            'medium' => '#053D4A',     // teal-deep Memora, 9.5:1
            'hard' => '#4C1D95',       // violet-900, 11:1
            'expert' => '#7C2D12',     // orange-900, 9.2:1
            'diabolical' => '#1f2937', // slate-800, 14:1
        ][$this->difficulty] ?? '#6B7280';
    }
}
