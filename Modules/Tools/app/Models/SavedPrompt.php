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
        'tags',
        'is_favorite',
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
        'tags' => 'array',
        'is_favorite' => 'boolean',
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

    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeSearch($query, string $term)
    {
        // Round 93 (2026-07-27, passe adversariale) : % et _ sont des jokers SQL LIKE - sans
        // échappement, une recherche contenant littéralement l'un de ces caractères (ex.
        // "réduction de 20%", "nom_variable") donne des résultats faux/imprévisibles. Clause
        // ESCAPE explicite (portable MySQL + SQLite - le caractère d'échappement par défaut du
        // LIKE n'est pas garanti identique entre les deux moteurs).
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);
        $like = '%'.$escaped.'%';

        return $query->where(function ($q) use ($like) {
            $q->whereRaw('name LIKE ? ESCAPE ?', [$like, '\\'])
                ->orWhereRaw('prompt_text LIKE ? ESCAPE ?', [$like, '\\']);
        });
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
