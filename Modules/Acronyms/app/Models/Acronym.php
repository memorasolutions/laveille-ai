<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Translatable\HasTranslations;

class Acronym extends Model implements Searchable
{
    use \Modules\Core\Traits\HasModerationStatus;
    use HasSuggestions;
    use HasTranslations;
    use LogsActivityStandard;
    use \Modules\Voting\Traits\HasCommunityVotes;

    protected array $activitylogFields = ['acronym', 'full_name', 'description', 'website_url', 'is_published'];
    protected string $activitylogName = 'acronym';

    protected array $suggestableFields = [
        'full_name' => 'Nom complet',
        'description' => 'Description',
        'website_url' => 'Site web',
        'other' => 'Autre',
    ];

    protected $table = 'acronyms';

    public array $translatable = ['acronym', 'full_name', 'slug', 'description'];

    protected $fillable = [
        'acronym',
        'full_name',
        'slug',
        'description',
        'website_url',
        'logo_url',
        'domain',
        'acronym_category_id',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AcronymCategory::class, 'acronym_category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOfDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    public static function searchableFields(): array
    {
        return ['acronym', 'full_name', 'description'];
    }

    public static function searchSectionKey(): string
    {
        return 'acronymes';
    }

    public static function searchSectionLabel(): string
    {
        return __('Acronymes');
    }

    public static function searchSectionIcon(): string
    {
        return '🔤';
    }

    public static function searchPriority(): int
    {
        return 50;
    }

    public function searchableResultTitle(): string
    {
        return $this->acronym;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->description ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('acronyms.show', $this->slug);
    }
}
