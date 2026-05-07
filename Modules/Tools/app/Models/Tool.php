<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tools\Models\Concerns\Shareable;

class Tool extends Model
{
    use Shareable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'featured_image',
        'is_active',
        'sort_order',
        'category',
        'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views_count' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
