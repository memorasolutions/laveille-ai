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
        'tracking_url', 'notes', 'gelato_submit_key', 'gelato_submit_started_at',
        'gelato_submit_state', 'gelato_issue', 'confirmation_sent_at', 'shipping_method_uid',
    ];

    /**
     * Composantes de taxe AFFICHÉES, dont la somme égale toujours le tax_amount FACTURÉ au cent près
     * (TVQ = taxe facturée - TPS, au lieu d'un 2e arrondi indépendant qui pouvait écarter de 0,01 $).
     *
     * @return array{tps: float, tvq: float}
     */
    public function taxLines(): array
    {
        $total = round((float) $this->tax_amount, 2);
        $tps = min($total, round((float) $this->subtotal * (float) config('shop.tax.tps', 5) / 100, 2));

        return ['tps' => $tps, 'tvq' => round($total - $tps, 2)];
    }

    /**
     * Lignes de taxe selon la province de livraison (TPS/TVQ, TVH, TPS seule), base = sous-total + livraison (manutention incluse).
     * Somme = tax_amount facturé. Si le barème actuel ne redonne pas la taxe stockée (commande antérieure au barème, ou
     * configuration changée depuis), une ligne unique « Taxes » du montant réellement facturé : jamais un détail inventé.
     *
     * @return list<array{code: string, label: string, rate: float, amount: float}>
     */
    public function taxBreakdown(): array
    {
        $billed = round((float) $this->tax_amount, 2);
        $address = (array) $this->shipping_address;
        $calc = app(\Modules\Shop\Services\TaxCalculator::class)->compute(
            (string) ($address['country'] ?? 'CA'),
            (string) ($address['state'] ?? ''),
            (float) $this->subtotal + (float) $this->shipping_cost
        );

        if ($calc['lines'] !== [] && abs($calc['total'] - $billed) < 0.005) {
            return $calc['lines'];
        }

        return $billed > 0 ? [['code' => 'TAXES', 'label' => 'Taxes', 'rate' => 0.0, 'amount' => $billed]] : [];
    }

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'gelato_submit_started_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
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
     * Numéro de commande unique et NON DEVINABLE : yyyymmdd-XXXXXXXXXX (10 caractères aléatoires, ~50 bits).
     * Il sert de preuve de possession au suivi invité (/suivi) : l'ancien format horodaté + 3 chiffres était énumérable.
     * Les commandes existantes gardent leur numéro (aucune réécriture).
     */
    public static function generateUniqueOrderNumber(int $attempts = 0): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // sans I, L, O, 0, 1 : lisible au téléphone
        $suffix = '';
        for ($i = 0; $i < 10; $i++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $number = Carbon::now()->format('Ymd') . '-' . $suffix;

        if ($attempts < 10 && static::withTrashed()->where('order_number', $number)->exists()) {
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
