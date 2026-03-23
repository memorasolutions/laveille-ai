<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class AcronymCategory extends Model
{
    use HasTranslations;

    protected $table = 'acronym_categories';

    public array $translatable = ['name', 'slug', 'description'];

    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'sort_order'];

    public function acronyms(): HasMany
    {
        return $this->hasMany(Acronym::class, 'acronym_category_id');
    }
}
