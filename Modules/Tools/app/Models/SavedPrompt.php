<?php

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SavedPrompt extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'saved_prompts';

    protected $fillable = [
        'user_id',
        'name',
        'prompt_text',
        'params',
        'is_public',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $prompt) {
            if (empty($prompt->public_id)) {
                do {
                    $id = Str::random(12);
                } while (static::where('public_id', $id)->exists());
                $prompt->public_id = $id;
            }
        });
    }

    protected $casts = [
        'params' => 'array',
        'is_public' => 'boolean',
    ];

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
