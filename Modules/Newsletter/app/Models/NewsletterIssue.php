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
        'content',
        'subscriber_count',
        'sent_at',
    ];

    protected $casts = [
        'content' => 'array',
        'sent_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('sent_at')->orderByDesc('sent_at');
    }

    public function getWebUrlAttribute(): string
    {
        return route('newsletter.web', ['year' => $this->year, 'week' => $this->week_number]);
    }
}
