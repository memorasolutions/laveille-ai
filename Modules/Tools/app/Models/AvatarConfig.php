<?php

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AvatarConfig extends Model
{
    protected $table = 'avatar_configs';

    protected $fillable = [
        'user_email',
        'slug',
        'config',
        'is_public',
        'views_count',
    ];

    protected $casts = [
        'config' => 'array',
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cfg) {
            if (empty($cfg->slug)) {
                $cfg->slug = Str::lower(Str::random(12));
            }
        });
    }
}
