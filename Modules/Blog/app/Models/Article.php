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
use Modules\Blog\States\DraftArticleState;
use Modules\Blog\States\PublishedArticleState;
use Modules\CustomFields\Traits\HasCustomFields;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\ModelStates\HasStates;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\Translatable\HasTranslations;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> published()
 */
class Article extends Model
{
    use BelongsToTenant, HasCustomFields, HasFactory, HasStates, HasTranslations, Searchable, SoftDeletes;

    public array $translatable = ['title', 'slug', 'content', 'excerpt'];

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
        'status',
        'published_at',
        'category',
        'category_id',
        'tags',
        'meta',
        'expired_at',
        'user_id',
        'tenant_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'status' => ArticleState::class,
        'tags' => 'array',
        'meta' => 'array',
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
}
