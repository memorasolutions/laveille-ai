<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shop\Models\Product;

class PrintFile extends Model
{
    protected $table = 'shop_print_files';

    // status, approved_hash, approved_at, approved_by : volontairement NON assignables en masse.
    // La seule voie vers APPROVED est PrintFileService::approve() (forceFill sous verrou).
    protected $fillable = [
        'product_id', 'variant_uid', 'variant_key', 'print_area', 'source_hash', 'print_file_hash',
        'public_url', 'rel_path', 'mockup_path', 'width_mm', 'height_mm',
        'zone_version', 'dpi', 'profile', 'engine_version', 'product_uid',
    ];

    protected static function booted(): void
    {
        // Sentinelle non nulle pour l'unicité (product_id, variant_key, print_area).
        static::saving(function (self $file) {
            $file->variant_key = (string) ($file->variant_uid ?? '');
        });
    }

    /** URL absolue https:// avec hôte (jamais relative, vide ou http). */
    public static function isAbsoluteHttpsUrl(?string $url): bool
    {
        if ($url === null || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' && ! empty(parse_url($url, PHP_URL_HOST));
    }

    protected $casts = [
        'status' => PrintFileStatus::class,
        'approved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Règle serveur non contournable : état APPROVED/SELLABLE ET hash approuvé = hash courant. */
    public function isOrderable(): bool
    {
        return $this->status->isOrderable()
            && ! empty($this->print_file_hash)
            && self::isAbsoluteHttpsUrl($this->public_url)
            && hash_equals((string) $this->approved_hash, (string) $this->print_file_hash);
    }
}
