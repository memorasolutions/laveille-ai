<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Models;

use Database\Factories\MetaTagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class MetaTag extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title', 'description', 'keywords', 'og_title', 'og_description'];

    protected static function newFactory(): MetaTagFactory
    {
        return MetaTagFactory::new();
    }

    protected $table = 'seo_meta_tags';

    protected $fillable = [
        'url_pattern',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'twitter_card',
        'robots',
        'canonical_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForUrl(Builder $query, string $url): void
    {
        $query->where('url_pattern', $url);
    }

    public static function findForUrl(string $url): ?self
    {
        $exact = static::active()->where('url_pattern', $url)->first();

        if ($exact) {
            return $exact;
        }

        return static::active()
            ->where('url_pattern', 'like', '%*%')
            ->get()
            ->first(function (self $metaTag) use ($url) {
                $regex = str_replace('\*', '[^/]*', preg_quote($metaTag->url_pattern, '#'));

                return (bool) preg_match('#^'.$regex.'$#', $url);
            });
    }
}
