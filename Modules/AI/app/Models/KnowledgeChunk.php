<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AI\Database\Factories\KnowledgeChunkFactory;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $table = 'ai_knowledge_chunks';

    const UPDATED_AT = null;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'embedding',
        'token_count',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'token_count' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /** @return array<float> */
    public function getEmbeddingArrayAttribute(): array
    {
        if (empty($this->embedding)) {
            return [];
        }

        $decoded = json_decode($this->embedding, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return [];
        }

        return array_map('floatval', $decoded);
    }

    protected static function newFactory(): KnowledgeChunkFactory
    {
        return KnowledgeChunkFactory::new();
    }
}
