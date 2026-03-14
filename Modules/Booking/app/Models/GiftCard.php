<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    protected $table = 'booking_gift_cards';

    protected $fillable = [
        'code', 'purchaser_name', 'purchaser_email',
        'recipient_name', 'recipient_email', 'recipient_message',
        'initial_amount', 'remaining_amount', 'currency',
        'status', 'purchased_at', 'expires_at',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active')->where('remaining_amount', '>', 0);
    }

    public function scopeByCode(Builder $query, string $code): void
    {
        $query->where('code', $code);
    }

    public function useAmount(float $amount): void
    {
        $this->remaining_amount = max(0, $this->remaining_amount - $amount);

        if ($this->remaining_amount <= 0) {
            $this->status = 'exhausted';
        }

        $this->save();
    }
}
