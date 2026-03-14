<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardUsage extends Model
{
    protected $table = 'booking_gift_card_usages';

    protected $fillable = ['gift_card_id', 'appointment_id', 'amount_used', 'used_at', 'note'];

    protected $casts = [
        'amount_used' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
