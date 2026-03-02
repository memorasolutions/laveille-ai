<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use BelongsToTenant, HasFactory, HasTranslations, Searchable, SoftDeletes;

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->getTranslation('name', app()->getLocale()),
            'description' => $this->getTranslation('description', app()->getLocale()),
        ];
    }

    public array $translatable = ['name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::saved(fn () => ResponseCache::clear());
        static::deleted(fn () => ResponseCache::clear());
    }

    protected $table = 'blog_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $category): void {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function (self $category): void {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }

    protected static function newFactory(): \Modules\Blog\Database\Factories\CategoryFactory
    {
        return \Modules\Blog\Database\Factories\CategoryFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $field = $field ?? $this->getRouteKeyName();

        if (in_array($field, $this->translatable)) {
            return $this->where("{$field}->{$this->getLocale()}", $value)->first();
        }

        return $this->where($field, $value)->first();
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
