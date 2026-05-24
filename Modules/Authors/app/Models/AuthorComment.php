<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'author_comments';

    protected $fillable = [
        'author_profile_id',
        'commentable_id',
        'commentable_type',
        'parent_id',
        'user_id',
        'author_name',
        'author_email',
        'body',
        'reactions',
        'ip_address',
        'user_agent',
        'spam_score',
        'approved_at',
        'flagged_at',
    ];

    protected $casts = [
        'reactions' => 'array',
        'approved_at' => 'datetime',
        'flagged_at' => 'datetime',
    ];

    /**
     * Set fixe des reactions emoji autorisées (charte 2026).
     */
    public const ALLOWED_REACTIONS = ['👍', '❤️', '😂', '🤔', '🎯'];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')->whereNull('flagged_at');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null && $this->flagged_at === null;
    }

    /**
     * TODO S107 : implémenter CommentSection Livewire component.
     * - Threading max 2 niveaux (lisibilité)
     * - Reactions emoji set fixe ALLOWED_REACTIONS
     * - Rate limit 5/min/IP via throttle
     * - Akismet integration pour spam_score auto-flag >threshold
     * - Notif email reply via Brevo workspace mailer
     * - Modération : auto-publish si user authentifié + trust score OK, sinon pending
     */
}
