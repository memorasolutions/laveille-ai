<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AI\Database\Factories\AiMessageFactory;
use Modules\AI\Enums\MessageRole;

class AiMessage extends Model
{
    use HasFactory;
    public const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens',
        'model',
        'metadata',
        'feedback',
        'feedback_comment',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'role' => MessageRole::class,
            'tokens' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    protected static function newFactory(): AiMessageFactory
    {
        return AiMessageFactory::new();
    }
}
