<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Faq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasScheduledPublishing;
use Modules\Tenancy\Traits\BelongsToTenant;

class Faq extends Model
{
    use BelongsToTenant, HasScheduledPublishing;

    protected string $publishedColumn = 'is_published';

    protected mixed $publishedValue = true;

    protected $fillable = ['question', 'answer', 'category', 'order', 'is_published', 'published_at', 'expired_at', 'tenant_id'];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function scopeByCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order');
    }

    public function safeAnswer(): string
    {
        return clean($this->answer);
    }
}
