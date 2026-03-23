<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Directory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Tool extends Model
{
    use HasTranslations;

    protected $table = 'directory_tools';

    public array $translatable = ['name', 'slug', 'description', 'short_description', 'how_to_use', 'core_features', 'use_cases', 'pros', 'cons'];

    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'url', 'logo',
        'pricing', 'status', 'clicks_count', 'is_featured', 'sort_order',
        'how_to_use', 'core_features', 'use_cases', 'faq', 'pros', 'cons',
        'screenshot', 'website_type', 'launch_year', 'target_audience',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'faq' => 'array',
        'target_audience' => 'array',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'directory_category_tool', 'directory_tool_id', 'directory_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'directory_tag_tool', 'directory_tool_id', 'directory_tag_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ToolReview::class, 'directory_tool_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(ToolDiscussion::class, 'directory_tool_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ToolResource::class, 'directory_tool_id');
    }

    public function averageRating(): float
    {
        return (float) $this->reviews()->approved()->avg('rating') ?: 0;
    }
}
