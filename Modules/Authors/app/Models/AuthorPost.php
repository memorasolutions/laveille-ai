<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\TracksEditorialModification;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AuthorPost extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditorialModification;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'body_markdown', 'status', 'visibility', 'tags', 'published_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('author_post');
    }

    // ACTION : proche de getActivitylogOptions() ci-dessus (+ excerpt/cover_image, réellement
    // affichés mais absents du journal d'audit) - propriété VOLONTAIREMENT distincte, voir
    // Modules\Core\Traits\TracksEditorialModification (DRY nuancé, CLAUDE.md).
    // MCP: SELF (<5 lignes)
    // RAISON: docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md, MESURE B.
    protected array $editorialFields = [
        'title', 'excerpt', 'body_markdown', 'cover_image', 'status', 'visibility', 'tags', 'published_at',
    ];

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
        'content_updated_at' => 'datetime',
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

    /**
     * Articles dont la PAGE doit exister et qui ont leur place dans une liste, quelle
     * que soit la restriction de lecture. A ne pas confondre avec scopePublic(), qui
     * ne retient que la visibilite strictement publique.
     *
     * Ticket #2445 : scopePublic() etait appele par les portes publiques, si bien qu'un
     * article « abonnes » ou « premium » renvoyait 404 a tout le monde, y compris a son
     * propre auteur. La page existe desormais ; c'est le CORPS qui est protege, par
     * isReadableBy().
     */
    public function scopeListable($query)
    {
        return $query->whereIn('visibility', [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_SUBSCRIBERS,
            self::VISIBILITY_PREMIUM,
        ]);
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

    /**
     * LA regle unique qui decide si le CORPS de l'article est lisible. Aucune vue,
     * aucun controleur, aucune commande ne redecide de ces conditions : tous appellent
     * cette methode (DRY strict - la connaissance vit a un seul endroit).
     */
    public function isReadableBy(?User $user): bool
    {
        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        // L'auteur lit toujours son propre article, quelle que soit la restriction.
        if ($this->authorProfile !== null && (int) $this->authorProfile->user_id === (int) $user->id) {
            return true;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->visibility === self::VISIBILITY_SUBSCRIBERS) {
            return AuthorSubscriber::query()
                ->where('author_profile_id', $this->author_profile_id)
                ->where('email', $user->email)
                ->confirmed()
                ->exists();
        }

        // ACTION : « premium » refuse meme a un abonne confirme.
        // MCP: SELF (<5 lignes)
        // RAISON : aucun mecanisme de paiement n'existe dans ce module, mesure le
        // 2026-09-11 (aucune table d'abonnement payant cote Authors). Refuser est le
        // seul comportement honnete ; rouvrir cette branche le jour ou le paiement existe.
        return false;
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
