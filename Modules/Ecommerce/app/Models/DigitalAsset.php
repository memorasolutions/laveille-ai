<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalAsset extends Model
{
    protected $table = 'ecommerce_digital_assets';

    protected $fillable = [
        'product_id', 'file_path', 'original_filename',
        'file_size', 'mime_type', 'download_limit', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'download_limit' => 'integer',
        'file_size' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DigitalAssetDownload::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
