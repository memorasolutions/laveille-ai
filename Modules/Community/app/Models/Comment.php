<?php

declare(strict_types=1);

namespace Modules\Community\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    use HasFactory;

    protected $table = 'community_comments';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'guest_name',
        'content',
        'parent_id',
        'status',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * #177 (2026-05-06) compat admin : la vue admin/blog/comments utilise
     * $comment->article et $comment->author. On expose ces alias via accessors
     * sans toucher la vue.
     */
    public function getArticleAttribute()
    {
        if ($this->commentable_type === \Modules\Blog\Models\Article::class) {
            return $this->commentable;
        }
        return null;
    }

    public function getAuthorAttribute()
    {
        return $this->user;
    }

    public function getGuestEmailAttribute()
    {
        // community_comments n'a pas de guest_email, retourne null pour compat vue
        return null;
    }

    public function authorName(): string
    {
        if ($this->user) return (string) $this->user->name;
        if ($this->guest_name) return (string) $this->guest_name;
        return __('Anonyme');
    }
}
