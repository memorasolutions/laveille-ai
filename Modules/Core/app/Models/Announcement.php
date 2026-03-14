<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Database\Factories\AnnouncementFactory;
use Modules\Core\Traits\HasScheduledPublishing;
use Modules\Tenancy\Traits\BelongsToTenant;

class Announcement extends Model
{
    use BelongsToTenant, HasFactory, HasScheduledPublishing;

    public const TYPES = ['feature', 'improvement', 'fix', 'announcement'];

    protected string $publishedColumn = 'is_published';

    protected mixed $publishedValue = true;

    protected $fillable = [
        'title',
        'body',
        'type',
        'version',
        'is_published',
        'published_at',
        'tenant_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function safeBody(): string
    {
        return clean($this->body);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'feature' => 'Nouveaute',
            'improvement' => 'Amelioration',
            'fix' => 'Correctif',
            default => 'Annonce',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'feature' => 'bg-success',
            'improvement' => 'bg-primary',
            'fix' => 'bg-warning text-dark',
            default => 'bg-info',
        };
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}
