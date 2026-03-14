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
use Modules\AI\Database\Factories\KnowledgeDocumentFactory;

class KnowledgeDocument extends Model
{
    use HasFactory;

    protected $table = 'ai_knowledge_documents';

    protected $fillable = [
        'title',
        'source_type',
        'source_id',
        'content',
        'metadata',
        'is_active',
        'last_synced_at',
        'tenant_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('source_type', $type);
    }

    protected static function newFactory(): KnowledgeDocumentFactory
    {
        return KnowledgeDocumentFactory::new();
    }
}
