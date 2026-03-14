<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AI\Database\Factories\KnowledgeUrlFactory;

class KnowledgeUrl extends Model
{
    use HasFactory;

    protected $table = 'ai_knowledge_urls';

    protected $fillable = [
        'url',
        'label',
        'hidden_source_name',
        'robots_allowed',
        'scrape_status',
        'scrape_frequency',
        'last_scraped_at',
        'pages_scraped',
        'max_pages',
        'scrape_error',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'robots_allowed' => 'boolean',
        'is_active' => 'boolean',
        'last_scraped_at' => 'datetime',
        'pages_scraped' => 'integer',
        'max_pages' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'robots_allowed' => false,
        'scrape_status' => 'pending',
        'scrape_frequency' => 'weekly',
        'pages_scraped' => 0,
        'max_pages' => 50,
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class, 'source_id')
            ->where('source_type', 'url');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeNeedsScraping(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('robots_allowed', true)
            ->where(function ($q) {
                $q->whereNull('last_scraped_at')
                    ->orWhere(function ($q2) {
                        $q2->where('scrape_frequency', 'daily')
                            ->where('last_scraped_at', '<', now()->subDay());
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('scrape_frequency', 'weekly')
                            ->where('last_scraped_at', '<', now()->subWeek());
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('scrape_frequency', 'monthly')
                            ->where('last_scraped_at', '<', now()->subMonth());
                    });
            });
    }

    protected static function newFactory(): KnowledgeUrlFactory
    {
        return KnowledgeUrlFactory::new();
    }
}
