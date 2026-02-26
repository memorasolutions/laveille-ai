<?php

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AI\Enums\MessageRole;

class AiMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens',
        'model',
        'metadata',
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
}
