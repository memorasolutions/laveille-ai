<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Mews\Purifier\Facades\Purifier;
use Modules\Blog\Database\Factories\ArticleFactory;
use Modules\Blog\States\ArticleState;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Contracts\Searchable as SearchableContract;
use Modules\Blog\States\DraftArticleState;
use Modules\Blog\States\PublishedArticleState;
use Modules\Core\Traits\HasPreviewToken;
use Modules\CustomFields\Traits\HasCustomFields;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\Translatable\HasTranslations;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> published()
 */
class Article extends Model implements SearchableContract
{
    use BelongsToTenant, HasAdminShareContents, HasCustomFields, HasFactory, HasPreviewToken, HasStates, HasTranslations, LogsActivity, Searchable, SoftDeletes;
    use \Modules\SEO\Traits\NotifiesIndexNow;

    public function getPublicUrl(): string
    {
        return url('/blog/' . $this->slug);
    }

    public array $translatable = ['title', 'slug', 'content', 'excerpt'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Article {$eventName}");
    }

    protected static function booted(): void
    {
        static::saved(fn () => ResponseCache::clear());
        static::deleted(fn () => ResponseCache::clear());
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'video_url',
        'video_summary',
        'status',
        'published_at',
        'category',
        'category_id',
        'tags',
        'meta',
        'expired_at',
        'user_id',
        'tenant_id',
        'is_featured',
        'content_password',
        'format',
        'preview_token',
        'submitted_by',
        'submission_status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'status' => ArticleState::class,
        'tags' => 'array',
        'meta' => 'array',
        'is_featured' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->whereState('status', PublishedArticleState::class)
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->whereState('status', DraftArticleState::class);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    /**
     * Recherche blog réutilisable et durcie (source de vérité unique).
     *
     * Échappe les wildcards LIKE (%, _, \) pour qu'ils soient traités
     * littéralement (un terme « 100% » ou « a_b » ne devient plus un joker)
     * et cible les 3 colonnes traduisibles (title/content/excerpt) sur la
     * locale courante. Terme vide => no-op (query inchangé).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $term
     * @param  string|null  $locale
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchText($query, ?string $term, ?string $locale = null)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $locale ??= app()->getLocale();
        // MySQL : le caractère d'échappement LIKE par défaut est le backslash.
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function ($q) use ($like, $locale) {
            $q->orWhere("title->{$locale}", 'like', $like)
                ->orWhere("content->{$locale}", 'like', $like)
                ->orWhere("excerpt->{$locale}", 'like', $like);
        });
    }

    public function isPasswordProtected(): bool
    {
        return ! empty($this->content_password);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->getTranslation('title', app()->getLocale()),
            'content' => $this->getTranslation('content', app()->getLocale()),
            'excerpt' => $this->getTranslation('excerpt', app()->getLocale()),
            'category' => $this->category,
            'tags' => $this->tags,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status instanceof PublishedArticleState;
    }

    protected function safeContent(): Attribute
    {
        return Attribute::get(fn () => Purifier::clean($this->content ?? ''));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $field = $field ?? $this->getRouteKeyName();

        if (in_array($field, $this->translatable)) {
            return $this->where("{$field}->{$this->getLocale()}", $value)->first();
        }

        return $this->where($field, $value)->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isGuestPost(): bool
    {
        return $this->submitted_by !== null;
    }

    public function getAuthorName(): string
    {
        if ($this->isGuestPost() && $this->submittedByUser) {
            return $this->submittedByUser->name;
        }

        return $this->user->name ?? 'Admin';
    }

    /**
     * Récupère le titre SEO depuis les métadonnées.
     */
    public function getSeoTitleAttribute(): ?string
    {
        return $this->meta['title'] ?? null;
    }

    public function scopePendingSubmissions($query)
    {
        return $query->where('submission_status', 'pending');
    }

    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tagsRelation(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->orderByDesc('revision_number');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('position');
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    public static function searchableFields(): array
    {
        return ['title', 'content', 'excerpt'];
    }

    public static function searchSectionKey(): string
    {
        return 'blog';
    }

    public static function searchSectionLabel(): string
    {
        return __('Blog');
    }

    public static function searchSectionIcon(): string
    {
        return '📝';
    }

    public static function searchPriority(): int
    {
        return 20;
    }

    public function searchableResultTitle(): string
    {
        return $this->title;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content ?: $this->excerpt ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('blog.show', $this->slug);
    }

    public function adminShareContents(): array
    {
        $title = $this->title ?? '';
        $excerpt = (string) ($this->excerpt ?? '');
        $resume = $this->stripLinks("# {$title}\n\n" . $excerpt . "\n\n" . strip_tags((string) ($this->content ?? '')));
        $prompt = $this->infographiePrompt('https://laveille.ai/blog', 'Vulgarise les idées clés de cet article dans une infographie engageante. Public : étudiants sans connaissances préalables.');
        $slides = $this->slidesPrompt('https://laveille.ai/blog', 'Objectif : vulgariser les idées clés de cet article et ce qu\'il faut en retenir. Public : étudiants, sans connaissances préalables.');
        // Post réseaux sociaux « 2026 » : titre en accroche + En clair (1re phrase) + 👉 (2e phrase) + CTA (sans lien ni promo).
        $sent = preg_split('/(?<=[.!?])\s+/', $excerpt, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $plainDef = isset($sent[0]) ? $this->smartTrim($this->stripLinks((string) $sent[0]), 200) : $this->smartTrim($this->stripLinks((string) $excerpt), 200);
        $interest = isset($sent[1]) ? $this->smartTrim($this->stripLinks((string) $sent[1]), 180) : '';
        $hook = $this->smartTrim(trim($title !== '' ? $title : $excerpt), 150);
        $cta = "Ça résonne avec ton expérience ? Dis-moi ce que t'en penses 👇";
        $tags = is_array($this->tags) ? $this->tags : [];
        $hashtags = array_merge(['#IA'], array_map(fn ($t) => '#' . $this->normalizeShareHashtag((string) $t), array_slice($tags, 0, 2)), ['#Québec', '#VeilleIA']);
        $linkedin = $this->buildLinkedInPost($hook, $plainDef, $interest, $cta, $hashtags);
        $facebook = $this->buildFacebookPost($hook, $plainDef, $interest, $cta, $hashtags);
        return [
            ['label' => 'Résumé (NotebookLM)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'NotebookLM Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'NotebookLM Diapositives', 'icon' => '🖼️', 'text' => $slides],
            ['label' => 'Post LinkedIn', 'icon' => '💼', 'text' => $linkedin],
            ['label' => 'Post Facebook', 'icon' => '📘', 'text' => $facebook],
        ];
    }
}
