<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Pages\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Laravel\Scout\Searchable;
use Modules\CustomFields\Traits\HasCustomFields;
use Modules\Pages\Database\Factories\StaticPageFactory;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    use HasCustomFields, HasFactory, HasTranslations, Searchable, SoftDeletes;

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
        'user_id',
    ];

    public const TEMPLATES = [
        'default'    => 'Standard',
        'full-width' => 'Pleine largeur',
        'sidebar'    => 'Avec barre latérale',
        'landing'    => 'Page d\'atterrissage',
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

    protected static function newFactory(): StaticPageFactory
    {
        return StaticPageFactory::new();
    }
}
