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
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasLifecycleStatus;
use Modules\Core\Traits\HasSponsorship;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Tool extends Model implements Searchable
{
    use HasLifecycleStatus;
    use HasSponsorship;
    use HasSuggestions;
    use HasTranslations;
    use LogsActivity;
    use \Modules\Voting\Traits\HasCommunityVotes;
    use \Modules\SEO\Traits\NotifiesIndexNow;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'url', 'pricing', 'status', 'short_description', 'description', 'is_featured', 'lifecycle_status', 'lifecycle_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('tool')
            ->setDescriptionForEvent(fn (string $event): string => match ($event) {
                'created' => 'Outil créé',
                'updated' => 'Outil modifié',
                'deleted' => 'Outil supprimé',
                default => "Outil {$event}",
            });
    }

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

    public array $translatable = ['name', 'slug', 'description', 'short_description', 'how_to_use', 'core_features', 'use_cases', 'pros', 'cons', 'education_pricing_details', 'review'];

    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'url', 'affiliate_url', 'logo',
        'pricing', 'status', 'clicks_count', 'is_featured', 'featured_until', 'featured_order', 'sort_order',
        'how_to_use', 'core_features', 'use_cases', 'faq', 'pros', 'cons',
        'screenshot', 'website_type', 'launch_year', 'target_audience',
        'submitted_by',
        'last_enriched_at', 'enrichment_version',
        'parent_tool_id', 'ecosystem_tag',
        'has_education_pricing', 'education_pricing_type', 'education_pricing_details', 'education_pricing_url',
        'education_discount_type', 'education_target_audience', 'education_verification_required', 'education_official_url', 'education_last_checked_at',
        'is_academic_discount', 'education_level', 'privacy_compliance', 'learning_curve', 'has_api_access',
        'lifecycle_status', 'lifecycle_date', 'lifecycle_replacement_url', 'lifecycle_replacement_tool_id', 'lifecycle_notes',
        'aliases',
        'review',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function parentTool(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_tool_id');
    }

    public function lifecycleReplacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'lifecycle_replacement_tool_id');
    }

    public function matchesName(string $candidate): int
    {
        $candidate = mb_strtolower(trim($candidate));
        if ($candidate === '') {
            return 0;
        }

        $names = [(string) ($this->getTranslation('name', 'fr_CA', false) ?? '')];

        if (is_array($this->aliases)) {
            foreach ($this->aliases as $alias) {
                if (is_string($alias) && trim($alias) !== '') {
                    $names[] = $alias;
                }
            }
        }

        $best = 0;

        foreach ($names as $n) {
            $n = mb_strtolower(trim($n));
            if ($n === '') {
                continue;
            }
            similar_text($candidate, $n, $percent);
            $best = (int) max($best, (int) round($percent));
        }

        return $best;
    }

    public function childTools(): HasMany
    {
        return $this->hasMany(self::class, 'parent_tool_id');
    }

    public function scopeEcosystem($query, string $tag)
    {
        return $query->where('ecosystem_tag', $tag);
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
        'has_education_pricing' => 'boolean',
        'education_target_audience' => 'array',
        'education_verification_required' => 'boolean',
        'education_last_checked_at' => 'datetime',
        'is_academic_discount' => 'boolean',
        'education_level' => 'array',
        'learning_curve' => 'integer',
        'has_api_access' => 'boolean',
        'featured_until' => 'datetime',
        'featured_order' => 'integer',
        'faq' => 'array',
        'target_audience' => 'array',
        'last_enriched_at' => 'datetime',
        'enrichment_version' => 'integer',
        'lifecycle_date' => 'date',
        'aliases' => 'array',
    ];

    public function setPricingAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['pricing'] = null;
            return;
        }

        $normalized = mb_strtolower(trim((string) $value));
        $normalized = str_replace(['open-source', 'open source', 'opensource'], 'open_source', $normalized);

        $this->attributes['pricing'] = $normalized;
    }

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
        return $query->where('is_featured', true)
            ->where(fn ($q) => $q->whereNull('featured_until')->orWhere('featured_until', '>', now()))
            ->orderBy('featured_order')
            ->orderByDesc('clicks_count');
    }

    public function scopeNotArchived($query)
    {
        return $query->where(function ($q) {
            $q->where('lifecycle_status', '!=', 'archived')
                ->orWhereNull('lifecycle_status');
        });
    }

    public static function pricingDistribution(): array
    {
        return self::published()->notArchived()
            ->selectRaw('pricing, count(*) as cnt')
            ->groupBy('pricing')
            ->pluck('cnt', 'pricing')
            ->toArray();
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

    public function alternatives(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'tool_alternatives', 'tool_id', 'alternative_tool_id')
            ->withPivot('relevance_score', 'source')
            ->withTimestamps();
    }

    public function alternativeOf(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'tool_alternatives', 'alternative_tool_id', 'tool_id')
            ->withPivot('relevance_score', 'source')
            ->withTimestamps();
    }

    public function allAlternatives()
    {
        return $this->alternatives->merge($this->alternativeOf)->unique('id');
    }

    public function averageRating(): float
    {
        return (float) $this->reviews()->approved()->avg('rating') ?: 0;
    }

    public static function searchableFields(): array
    {
        return ['name', 'short_description', 'description'];
    }

    public static function searchSectionKey(): string
    {
        return 'annuaire';
    }

    public static function searchSectionLabel(): string
    {
        return __('Annuaire');
    }

    public static function searchSectionIcon(): string
    {
        return '🛠️';
    }

    public static function searchPriority(): int
    {
        return 30;
    }

    public function searchableResultTitle(): string
    {
        return $this->name;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->description ?: $this->short_description ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('directory.show', $this->slug);
    }
}
