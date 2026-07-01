<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Palier d'abonnement Academy (freemium/pro/organisation — LMS 2026). Un palier
 * regroupe une liste de clés de fonctionnalités Academy (`features`, comparées
 * aux drapeaux `academy.*_enabled` existants) débloquées pour les utilisateurs
 * qui y sont assignés (voir UserSubscriptionTier + Services\TierGateService).
 *
 * `price_cents`/`stripe_price_id` sont des données NEUTRES configurables par
 * l'admin (voir Http\Controllers\Admin\AdminSubscriptionTierController) : aucun
 * produit/prix Stripe réel n'est créé par le code de ce module.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property int|null    $price_cents
 * @property string      $billing_period
 * @property array|null  $features
 * @property int|null    $max_seats
 * @property string|null $stripe_price_id
 * @property bool        $is_default
 * @property bool        $is_active
 * @property int         $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SubscriptionTier extends Model
{
    protected $table = 'academy_subscription_tiers';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'billing_period',
        'features',
        'max_seats',
        'stripe_price_id',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'max_seats'   => 'integer',
        'sort_order'  => 'integer',
        'features'    => 'array',
        'is_default'  => 'boolean',
        'is_active'   => 'boolean',
    ];

    /** Vrai si ce palier débloque la fonctionnalité Academy identifiée par $key. */
    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->features ?? [], true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price_cents');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserSubscriptionTier::class, 'subscription_tier_id');
    }

    /** Libellé de prix lisible pour l'admin/UI (« Gratuit » si aucun prix configuré). */
    public function getPriceLabelAttribute(): string
    {
        if ($this->price_cents === null || $this->price_cents === 0) {
            return 'Gratuit';
        }

        return number_format($this->price_cents / 100, 2, ',', "\u{202F}")."\u{2009}$";
    }
}
