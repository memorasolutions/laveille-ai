<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Blog\Database\Factories\CommentFactory;
use Modules\Blog\States\ApprovedCommentState;
use Modules\Blog\States\CommentState;
use Modules\Blog\States\PendingCommentState;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\ModelStates\HasStates;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * @method static \Illuminate\Database\Eloquent\Builder approved()
 * @method static \Illuminate\Database\Eloquent\Builder pending()
 */
class Comment extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasStates;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saved(fn () => ResponseCache::clear());
        static::deleted(fn () => ResponseCache::clear());
    }

    protected $table = 'blog_comments';

    protected $fillable = [
        'article_id',
        'user_id',
        'guest_name',
        'guest_email',
        'content',
        'status',
        'parent_id',
        'tenant_id',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
        'status' => CommentState::class,
    ];

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeApproved($query): mixed
    {
        return $query->whereState('status', ApprovedCommentState::class);
    }

    public function scopePending($query): mixed
    {
        return $query->whereState('status', PendingCommentState::class);
    }

    public function authorName(): string
    {
        if ($this->user_id && $this->author) {
            return $this->author->name;
        }

        return $this->guest_name ?? 'Anonyme';
    }

    public function isApproved(): bool
    {
        return $this->status->equals(ApprovedCommentState::class);
    }
}
