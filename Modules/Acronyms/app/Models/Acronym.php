<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Acronym extends Model
{
    use HasTranslations;

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
}
