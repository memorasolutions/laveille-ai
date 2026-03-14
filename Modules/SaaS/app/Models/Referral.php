<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_REWARDED = 'rewarded';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'code',
        'status',
        'reward_type',
        'reward_value',
        'rewarded_at',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_value' => 'decimal:2',
            'rewarded_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function scopeForReferrer(Builder $query, int $userId): Builder
    {
        return $query->where('referrer_id', $userId);
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
