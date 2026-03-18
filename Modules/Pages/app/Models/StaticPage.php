<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Pages\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Mews\Purifier\Facades\Purifier;
use Modules\Core\Traits\HasPreviewToken;
use Modules\Core\Traits\HasRevisions;
use Modules\Core\Traits\HasScheduledPublishing;
use Modules\CustomFields\Traits\HasCustomFields;
use Modules\Pages\Database\Factories\StaticPageFactory;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    use BelongsToTenant, HasCustomFields, HasFactory, HasPreviewToken, HasRevisions, HasScheduledPublishing, HasTranslations, LogsActivity, Searchable, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Page {$eventName}");
    }

    /** @var list<string> */
    protected array $revisionable = ['title', 'content', 'excerpt', 'status', 'template', 'meta_title', 'meta_description'];

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->getTranslation('title', app()->getLocale()),
            'content' => $this->getTranslation('content', app()->getLocale()),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    public array $translatable = ['title', 'slug', 'content', 'excerpt', 'meta_title', 'meta_description'];

    protected $table = 'static_pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'meta_title',
        'meta_description',
        'template',
        'published_at',
        'expired_at',
        'user_id',
        'tenant_id',
        'content_password',
        'preview_token',
        'parent_id',
        'sort_order',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public const TEMPLATES = [
        'default' => 'Standard',
        'full-width' => 'Pleine largeur',
        'sidebar' => 'Avec barre latérale',
        'landing' => 'Page d\'atterrissage',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

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
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPasswordProtected(): bool
    {
        return ! empty($this->content_password);
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('sort_order');
    }

    public function ancestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent instanceof static) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors->reverse()->values();
    }

    public function depth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent instanceof static) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function breadcrumb(): array
    {
        return $this->ancestors()->push($this)->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): StaticPageFactory
    {
        return StaticPageFactory::new();
    }
}
