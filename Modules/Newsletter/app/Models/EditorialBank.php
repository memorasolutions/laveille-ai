<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EditorialBank extends Model
{
    protected $table = 'editorial_bank';

    protected $fillable = [
        'theme', 'content', 'author', 'used_count', 'last_used_at', 'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByTheme(Builder $query, string $theme): Builder
    {
        return $query->where('theme', $theme);
    }

    public static function getNextEditorial(): ?self
    {
        $editorial = self::active()
            ->orderBy('used_count', 'asc')
            ->orderByRaw('last_used_at IS NOT NULL, last_used_at ASC')
            ->first();

        if ($editorial) {
            $editorial->increment('used_count');
            $editorial->update(['last_used_at' => Carbon::now()]);
        }

        return $editorial;
    }
}
