<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AI\Database\Factories\ChannelMessageFactory;

class ChannelMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'external_id',
        'external_thread_id',
        'direction',
        'status',
        'subject',
        'body',
        'sender',
        'recipient',
        'payload',
        'ticket_id',
        'conversation_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('ticket_id')->whereNull('conversation_id');
    }

    protected static function newFactory(): ChannelMessageFactory
    {
        return ChannelMessageFactory::new();
    }
}
