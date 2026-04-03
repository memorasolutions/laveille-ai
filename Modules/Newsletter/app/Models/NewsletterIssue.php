<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterIssue extends Model
{
    protected $fillable = [
        'week_number',
        'year',
        'subject',
        'status',
        'content',
        'editorial_edited',
        'subscriber_count',
        'sent_at',
        'edited_at',
    ];

    protected $casts = [
        'content' => 'array',
        'sent_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isReady(): bool
    {
        return in_array($this->status, ['draft', 'ready']);
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('sent_at')->orderByDesc('sent_at');
    }

    public function getWebUrlAttribute(): string
    {
        return route('newsletter.web', ['year' => $this->year, 'week' => $this->week_number]);
    }
}
