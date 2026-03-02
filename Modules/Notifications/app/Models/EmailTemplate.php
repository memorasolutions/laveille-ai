<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Traits\HasRevisions;
use Modules\Notifications\Database\Factories\EmailTemplateFactory;

class EmailTemplate extends Model
{
    use HasFactory, HasRevisions;

    /** @var list<string> */
    protected array $revisionable = ['name', 'subject', 'body_html', 'variables', 'json_content'];
    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body_html',
        'variables',
        'json_content',
        'category',
        'is_active',
        'module',
        'tenant_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'json_content' => 'array',
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

    protected static function newFactory(): EmailTemplateFactory
    {
        return EmailTemplateFactory::new();
    }
}
