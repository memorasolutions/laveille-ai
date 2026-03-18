<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCartReminder extends Model
{
    protected $table = 'ecommerce_abandoned_cart_reminders';

    protected $fillable = [
        'cart_id', 'user_id', 'reminder_number',
        'sent_at', 'clicked_at', 'recovered_at',
    ];

    protected $casts = [
        'reminder_number' => 'integer',
        'sent_at' => 'datetime',
        'clicked_at' => 'datetime',
        'recovered_at' => 'datetime',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNotRecovered(Builder $query): Builder
    {
        return $query->whereNull('recovered_at');
    }
}
