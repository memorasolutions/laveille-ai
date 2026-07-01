<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Assignation historisée d'un SubscriptionTier à un utilisateur. Voir
 * Services\TierGateService::currentTierFor() qui résout l'assignation active
 * la plus récente (à défaut, retombe sur le palier `is_default`).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int         $subscription_tier_id
 * @property bool        $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserSubscriptionTier extends Model
{
    protected $table = 'academy_user_subscription_tiers';

    protected $fillable = [
        'user_id',
        'subscription_tier_id',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'subscription_tier_id');
    }

    /** Assignations actives et non expirées (ends_at null ou dans le futur). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
