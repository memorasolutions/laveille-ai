<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Dictionary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    protected $table = 'dictionary_categories';

    public array $translatable = ['name', 'slug', 'description'];

    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'sort_order'];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'dictionary_category_id');
    }
}
