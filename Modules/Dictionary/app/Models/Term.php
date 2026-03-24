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
use Spatie\Translatable\HasTranslations;

class Term extends Model
{
    use HasTranslations;

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
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'dictionary_category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
