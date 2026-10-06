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

    protected $fillable = [
        'product_id', 'variant_uid', 'print_area', 'status', 'source_hash', 'print_file_hash',
        'approved_hash', 'public_url', 'rel_path', 'mockup_path', 'width_mm', 'height_mm',
        'zone_version', 'dpi', 'profile', 'engine_version', 'approved_at', 'approved_by',
    ];

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
            && ! empty($this->public_url)
            && hash_equals((string) $this->approved_hash, (string) $this->print_file_hash);
    }
}
