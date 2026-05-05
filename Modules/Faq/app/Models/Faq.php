<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Faq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasPublishedState;
use Modules\Core\Traits\HasScheduledPublishing;
use Modules\Faq\Database\Factories\FaqFactory;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Faq extends Model
{
    use BelongsToTenant, HasFactory, HasPublishedState, HasScheduledPublishing, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "FAQ {$eventName}");
    }

    protected string $publishedColumn = 'is_published';

    protected mixed $publishedValue = true;

    protected $fillable = ['question', 'answer', 'category', 'order', 'is_published', 'published_at', 'expired_at', 'tenant_id'];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    // 2026-05-05 #144 : scopePublished mutualise via HasPublishedState (DRY Core).

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

    protected static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
    }
}
