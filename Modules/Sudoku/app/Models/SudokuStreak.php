<?php

declare(strict_types=1);

namespace Modules\Sudoku\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SudokuStreak extends Model
{
    use HasFactory;

    protected $table = 'sudoku_streaks';

    protected $fillable = [
        'user_id', 'pseudo_hash', 'current_streak', 'longest_streak',
        'last_played_date', 'total_completed',
    ];

    protected $casts = [
        'last_played_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Met à jour le streak au moment d'une complétion réussie.
     * Si dernière partie hier → +1, si même jour → no-op, sinon reset à 1.
     */
    public function recordCompletion(): void
    {
        $today = now()->toDateString();
        $last = optional($this->last_played_date)->toDateString();

        if ($last === $today) {
            $this->total_completed++;
            $this->save();
            return;
        }

        if ($last === now()->subDay()->toDateString()) {
            $this->current_streak++;
        } else {
            $this->current_streak = 1;
        }

        $this->longest_streak = max($this->longest_streak, $this->current_streak);
        $this->last_played_date = $today;
        $this->total_completed++;
        $this->save();
    }
}
