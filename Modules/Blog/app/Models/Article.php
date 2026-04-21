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
    use BelongsToTenant, HasCustomFields, HasFactory, HasPreviewToken, HasStates, HasTranslations, LogsActivity, Searchable, SoftDeletes;
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
}
