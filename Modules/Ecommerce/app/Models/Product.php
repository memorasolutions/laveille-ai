<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'ecommerce_products';

    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'price', 'compare_price',
        'sku', 'is_active', 'is_featured', 'weight', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'weight' => 'decimal:2',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'ecommerce_product_category');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function digitalAssets(): HasMany
    {
        return $this->hasMany(DigitalAsset::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued()
            ->optimize();

        $this->addMediaConversion('medium')
            ->fit(Fit::Contain, 600, 600)
            ->nonQueued()
            ->optimize();

        $this->addMediaConversion('large')
            ->fit(Fit::Contain, 1200, 1200)
            ->nonQueued()
            ->optimize();
    }

    public function crossSells(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'ecommerce_related_products', 'product_id', 'related_product_id')
            ->wherePivot('type', 'cross_sell')
            ->withPivot('type', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function upSells(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'ecommerce_related_products', 'product_id', 'related_product_id')
            ->wherePivot('type', 'up_sell')
            ->withPivot('type', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
