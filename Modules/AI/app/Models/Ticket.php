<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AI\Database\Factories\TicketFactory;
use Modules\AI\Enums\TicketPriority;
use Modules\AI\Enums\TicketStatus;
use Modules\Tenancy\Traits\BelongsToTenant;

class Ticket extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'title', 'description', 'status', 'priority',
        'user_id', 'agent_id', 'conversation_id', 'sla_policy_id',
        'category', 'due_at', 'first_response_at', 'resolved_at',
        'closed_at', 'csat_score', 'csat_comment', 'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'csat_score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            $ticket->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TicketTag::class, 'ticket_tag', 'ticket_id', 'tag_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', TicketStatus::Open);
    }

    public function scopeByPriority($query, TicketPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [TicketStatus::Closed, TicketStatus::Resolved])
            ->where('due_at', '<', now());
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }
}
