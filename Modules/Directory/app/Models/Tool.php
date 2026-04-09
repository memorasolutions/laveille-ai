<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Translatable\HasTranslations;

class Tool extends Model
{
    use HasSuggestions;
    use HasTranslations;
    use \Modules\Voting\Traits\HasCommunityVotes;
    use \Modules\SEO\Traits\NotifiesIndexNow;

    public function getPublicUrl(): string
    {
        return route('directory.show', $this->slug);
    }

    protected array $suggestableFields = [
        'description' => 'Description',
        'short_description' => 'Description courte',
        'pricing' => 'Tarification',
        'url' => 'URL',
        'core_features' => 'Fonctionnalités',
        'how_to_use' => 'Guide',
        'use_cases' => "Cas d'usage",
        'other' => 'Autre',
    ];

    protected $table = 'directory_tools';

    public array $translatable = ['name', 'slug', 'description', 'short_description', 'how_to_use', 'core_features', 'use_cases', 'pros', 'cons'];

    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'url', 'affiliate_url', 'logo',
        'pricing', 'status', 'clicks_count', 'is_featured', 'sort_order',
        'how_to_use', 'core_features', 'use_cases', 'faq', 'pros', 'cons',
        'screenshot', 'website_type', 'launch_year', 'target_audience',
        'submitted_by',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function getVisitUrl(): string
    {
        return $this->affiliate_url ?: $this->url ?? '#';
    }

    public function isAffiliate(): bool
    {
        return ! empty($this->affiliate_url);
    }

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

    public function screenshots(): HasMany
    {
        return $this->hasMany(ToolScreenshot::class, 'directory_tool_id');
    }

    public function averageRating(): float
    {
        return (float) $this->reviews()->approved()->avg('rating') ?: 0;
    }
}
