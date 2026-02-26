<?php

declare(strict_types=1);

namespace Modules\Editor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Editor\Database\Factories\ShortcodeFactory;

class Shortcode extends Model
{
    use HasFactory;

    protected $table = 'shortcodes';

    protected $fillable = [
        'tag',
        'name',
        'description',
        'html_template',
        'parameters',
        'has_content',
        'is_active',
    ];

    protected $casts = [
        'parameters' => 'array',
        'has_content' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): ShortcodeFactory
    {
        return ShortcodeFactory::new();
    }
}
