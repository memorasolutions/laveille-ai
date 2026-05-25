<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AuthorPost extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'body_markdown', 'status', 'visibility', 'tags', 'published_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('author_post');
    }

    protected $table = 'author_posts';

    protected $fillable = [
        'author_profile_id',
        'slug',
        'title',
        'excerpt',
        'body_markdown',
        'body_html',
        'cover_image',
        'status',
        'visibility',
        'tags',
        'reading_time_minutes',
        'views_count',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ARCHIVED = 'archived';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_SUBSCRIBERS = 'subscribers';
    public const VISIBILITY_PREMIUM = 'premium';

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(AuthorPostRevision::class)->latest();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(AuthorComment::class, 'commentable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('published_at');
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function isPaywalled(): bool
    {
        return $this->visibility === self::VISIBILITY_PREMIUM;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * TODO S107 : implémenter AuthorEditor Livewire component avec EasyMDE.
     * - Stack : EasyMDE CDN (60KB) + Tailwind Typography + Spatie/Image upload
     * - Auto-save toutes 30s en localStorage + draft DB
     * - Slash commands : /image /quote /code /embed /toc /poll /tip-button
     * - Toolbar minimal : H1-H6, bold, italic, link, image drag-drop, embed auto-detect
     * - Side-by-side live preview Tailwind Typography render
     * - Mobile responsive (collapse toolbar)
     * - Reuse ModerationPipelineService pour scan auto avant publish
     * - Generate body_html depuis body_markdown via league/commonmark
     * - Calculate reading_time_minutes via word count / 200wpm
     */
}
