<?php

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SavedCrosswordPreset extends Model
{
    use SoftDeletes;

    protected $table = 'saved_crossword_presets';

    protected $fillable = ['user_id', 'name', 'config_text', 'params', 'is_public'];

    protected $casts = [
        'params' => 'array',
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $preset) {
            if (empty($preset->public_id)) {
                do {
                    $id = Str::random(12);
                } while (static::where('public_id', $id)->exists());
                $preset->public_id = $id;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
