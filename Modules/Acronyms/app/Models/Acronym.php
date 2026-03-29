<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Translatable\HasTranslations;

class Acronym extends Model
{
    use HasSuggestions;
    use HasTranslations;
    use \Modules\Voting\Traits\HasCommunityVotes;

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
}
