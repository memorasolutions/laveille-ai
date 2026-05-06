<?php

declare(strict_types=1);

namespace Modules\Sudoku\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSudokuPreset extends Model
{
    protected $table = 'saved_sudoku_presets';

    protected $fillable = [
        'user_id', 'pseudo', 'puzzle_id', 'grid_state',
        'time_elapsed', 'hints_used', 'errors_count', 'last_saved_at',
    ];

    protected $casts = [
        'grid_state' => 'array',
        'last_saved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(SudokuPuzzle::class, 'puzzle_id');
    }

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPseudo(Builder $query, ?string $pseudo): Builder
    {
        return $query->where('pseudo', $pseudo);
    }
}
