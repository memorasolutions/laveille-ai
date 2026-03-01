<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'type',
        'url',
        'route_name',
        'linkable_type',
        'linkable_id',
        'target',
        'icon',
        'css_classes',
        'order',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function (Builder $builder) {
            $builder->orderBy('menu_items.order');
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolveUrl(): string
    {
        return match ($this->type) {
            'custom' => $this->url ?? '#',
            'route' => $this->route_name ? route($this->route_name) : '#',
            'page', 'category' => $this->linkable?->url ?? $this->linkable?->slug ?? '#',
            default => '#',
        };
    }

    public function isActive(): bool
    {
        $resolved = $this->resolveUrl();

        if ($resolved === '#') {
            return $this->children->contains(fn (self $child) => $child->isActive());
        }

        return request()->fullUrlIs(url($resolved) . '*');
    }
}
