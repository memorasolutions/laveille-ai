<?php

declare(strict_types=1);

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body_html',
        'variables',
        'is_active',
        'module',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $model): void {
            Cache::forget("email_template.{$model->slug}");
        });
        static::deleted(function (self $model): void {
            Cache::forget("email_template.{$model->slug}");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findBySlug(string $slug): ?self
    {
        return Cache::remember("email_template.{$slug}", 3600, fn () => static::active()->where('slug', $slug)->first());
    }
}
