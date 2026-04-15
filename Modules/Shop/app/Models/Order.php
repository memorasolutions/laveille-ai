<?php

namespace Modules\Shop\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'shop_orders';

    protected $fillable = [
        'order_number', 'user_id', 'email', 'stripe_session_id', 'stripe_payment_intent_id',
        'gelato_order_id', 'status', 'subtotal', 'tax_amount', 'shipping_cost',
        'total', 'shipping_address', 'billing_address', 'tracking_number',
        'tracking_url', 'notes',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateUniqueOrderNumber();
            }
        });
    }

    /**
     * Génère un numéro de commande unique : yyyymmddHHmmss-XXX
     */
    public static function generateUniqueOrderNumber(int $attempts = 0): string
    {
        if ($attempts >= 10) {
            return Carbon::now()->format('Ymd-His') . '-' . random_int(10000, 99999);
        }

        $number = Carbon::now()->format('Ymd-His') . '-' . random_int(100, 999);

        if (static::where('order_number', $number)->exists()) {
            return self::generateUniqueOrderNumber($attempts + 1);
        }

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
