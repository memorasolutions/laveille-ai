<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'shop_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'variant_label', 'quantity',
        'unit_price', 'gelato_variant_id', 'gelato_store_product_variant_id',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * withTrashed : un produit archivé (SoftDeletes) APRÈS le paiement reste lisible pour sa commande payée.
     * Sans cela, la relation renvoie null et l'article déjà encaissé est refusé avant même de lire son identifiant figé.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }
}
