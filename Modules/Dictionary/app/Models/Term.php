<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Dictionary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasPublishedState;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Translatable\HasTranslations;

class Term extends Model implements Searchable
{
    use HasPublishedState;
    use HasSuggestions;
    use HasTranslations;
    use LogsActivityStandard;

    protected array $activitylogFields = ['name', 'definition', 'analogy', 'example', 'did_you_know', 'is_published'];
    protected string $activitylogName = 'term';

    protected array $suggestableFields = [
        'definition' => 'Définition',
        'analogy' => 'Analogie',
        'example' => 'Exemple',
        'did_you_know' => 'Le saviez-vous',
        'other' => 'Autre',
    ];

    protected $table = 'dictionary_terms';

    public array $translatable = ['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know'];

    protected $fillable = [
        'name',
        'acronym_full',
        'slug',
        'definition',
        'analogy',
        'example',
        'did_you_know',
        'difficulty',
        'icon',
        'hero_image',
        'type',
        'dictionary_category_id',
        'is_published',
        'match_strategy', // 2026-05-05 #145 WSD : loose | case_sensitive | exact_phrase | never_auto
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'dictionary_category_id');
    }

    // 2026-05-05 #144 : scopePublished mutualise via HasPublishedState (DRY Core).

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public static function searchableFields(): array
    {
        return ['name', 'definition', 'analogy', 'example', 'did_you_know'];
    }

    public static function searchSectionKey(): string
    {
        return 'glossaire';
    }

    public static function searchSectionLabel(): string
    {
        return __('Glossaire');
    }

    public static function searchSectionIcon(): string
    {
        return '📚';
    }

    public static function searchPriority(): int
    {
        return 40;
    }

    public function searchableResultTitle(): string
    {
        return $this->name;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->definition ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('dictionary.show', $this->slug);
    }
}
