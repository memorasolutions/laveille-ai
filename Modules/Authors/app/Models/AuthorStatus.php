<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'author_statuses';

    protected $fillable = [
        'author_profile_id',
        'content',
        'content_type',
        'image_url',
        'is_published',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeShort($query)
    {
        return $query->where('content_type', 'short');
    }

    public function scopeLong($query)
    {
        return $query->where('content_type', 'long');
    }

    public function setContentAttribute(string $value): void
    {
        $contentType = $this->content_type ?? 'long';
        $this->attributes['content'] = $contentType === 'short' ? mb_substr($value, 0, 280) : $value;
    }

    public function getExcerptAttribute(): string
    {
        $text = strip_tags((string) $this->content);
        return mb_substr($text, 0, 100).(mb_strlen($text) > 100 ? '...' : '');
    }
}
