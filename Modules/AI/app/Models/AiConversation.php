<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\AI\Database\Factories\AiConversationFactory;
use Modules\AI\Enums\ConversationStatus;
use Modules\Tenancy\Traits\BelongsToTenant;

class AiConversation extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'title',
        'status',
        'model',
        'system_prompt',
        'context',
        'metadata',
        'agent_id',
        'tokens_used',
        'cost_estimate',
        'closed_at',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'metadata' => 'array',
            'status' => ConversationStatus::class,
            'closed_at' => 'datetime',
            'tokens_used' => 'integer',
            'cost_estimate' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNot('status', ConversationStatus::Closed);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, ConversationStatus $status)
    {
        return $query->where('status', $status);
    }

    protected static function newFactory(): AiConversationFactory
    {
        return AiConversationFactory::new();
    }
}
