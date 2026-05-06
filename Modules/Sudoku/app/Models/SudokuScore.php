<?php

declare(strict_types=1);

namespace Modules\Sudoku\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SudokuScore extends Model
{
    use HasFactory;

    protected $table = 'sudoku_scores';

    protected $fillable = [
        'puzzle_id', 'user_id', 'pseudo', 'ip_hash', 'country',
        'time_seconds', 'hints_used', 'errors_count', 'score',
        'completed_at', 'is_published_in_leaderboard',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'is_published_in_leaderboard' => 'boolean',
    ];

    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(SudokuPuzzle::class, 'puzzle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published_in_leaderboard', true);
    }

    public function scopeTopByDifficulty($query, string $difficulty)
    {
        return $query->join('sudoku_puzzles', 'sudoku_scores.puzzle_id', '=', 'sudoku_puzzles.id')
            ->where('sudoku_puzzles.difficulty', $difficulty)
            ->orderBy('sudoku_scores.score', 'desc')
            ->orderBy('sudoku_scores.time_seconds', 'asc')
            ->select('sudoku_scores.*');
    }

    public function scopeTopToday($query, string $difficulty)
    {
        return $this->scopeTopByDifficulty($query, $difficulty)
            ->whereDate('sudoku_puzzles.date', now()->toDateString());
    }

    public function scopeTopWeek($query, string $difficulty)
    {
        return $this->scopeTopByDifficulty($query, $difficulty)
            ->whereDate('sudoku_puzzles.date', '>=', now()->subDays(7)->toDateString());
    }

    public function scopeTopMonth($query, string $difficulty)
    {
        return $this->scopeTopByDifficulty($query, $difficulty)
            ->whereDate('sudoku_puzzles.date', '>=', now()->subDays(30)->toDateString());
    }

    public function scopeTopByCountry($query, string $country)
    {
        return $query->where('country', $country)->orderBy('score', 'desc');
    }
}
