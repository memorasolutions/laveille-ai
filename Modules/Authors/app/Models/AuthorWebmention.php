<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorWebmention extends Model
{
    use SoftDeletes;

    public const TYPES = ['mention', 'reply', 'like', 'repost', 'bookmark'];

    protected $fillable = [
        'author_post_id',
        'target_url',
        'source_url',
        'source_author_name',
        'source_author_url',
        'source_excerpt',
        'type',
        'received_at',
        'verified_at',
        'spam_score',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'verified_at' => 'datetime',
        'spam_score' => 'integer',
    ];

    public function authorPost(): BelongsTo
    {
        return $this->belongsTo(AuthorPost::class, 'author_post_id');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeNotSpam($query)
    {
        return $query->where('spam_score', '<', 50);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
