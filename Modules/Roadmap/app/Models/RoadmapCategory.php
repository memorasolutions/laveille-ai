<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Roadmap\Database\Factories\RoadmapCategoryFactory;

class RoadmapCategory extends Model
{
    use HasFactory;

    protected $table = 'roadmap_categories';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'sort_order',
        'tenant_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    protected static function newFactory(): RoadmapCategoryFactory
    {
        return RoadmapCategoryFactory::new();
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class, 'category_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
