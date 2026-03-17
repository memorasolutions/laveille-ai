<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Ecommerce\Database\Factories\PromotionFactory;

class Promotion extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_promotions';

    protected $fillable = [
        'name', 'type', 'value', 'conditions', 'tiers', 'bogo_config',
        'applies_to', 'target_ids', 'priority', 'is_stackable', 'is_automatic',
        'max_uses', 'used_count', 'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'conditions' => 'array',
        'tiers' => 'array',
        'bogo_config' => 'array',
        'target_ids' => 'array',
        'is_stackable' => 'boolean',
        'is_automatic' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const TYPE_PERCENTAGE = 'percentage_off';

    public const TYPE_FIXED = 'fixed_off';

    public const TYPE_BOGO = 'bogo';

    public const TYPE_FREE_SHIPPING = 'free_shipping';

    public const TYPE_TIERED = 'tiered_pricing';

    public const APPLIES_ALL = 'all';

    public const APPLIES_PRODUCTS = 'specific_products';

    public const APPLIES_CATEGORIES = 'specific_categories';

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $this->starts_at->greaterThan($now)) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->lessThan($now)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('is_automatic', true);
    }

    public function scopeValid(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('max_uses')->orWhereRaw('used_count < max_uses'));
    }

    public function appliesToProduct(int $productId): bool
    {
        if ($this->applies_to === self::APPLIES_ALL) {
            return true;
        }

        if ($this->applies_to === self::APPLIES_PRODUCTS) {
            return in_array($productId, $this->target_ids ?? [], true);
        }

        return false;
    }

    public function appliesToCategory(int $categoryId): bool
    {
        if ($this->applies_to === self::APPLIES_ALL) {
            return true;
        }

        if ($this->applies_to === self::APPLIES_CATEGORIES) {
            return in_array($categoryId, $this->target_ids ?? [], true);
        }

        return false;
    }

    public function meetsConditions(float $subtotal, int $qty): bool
    {
        $conditions = $this->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        if (isset($conditions['min_order']) && $subtotal < (float) $conditions['min_order']) {
            return false;
        }

        if (isset($conditions['min_qty']) && $qty < (int) $conditions['min_qty']) {
            return false;
        }

        return true;
    }

    protected static function newFactory(): PromotionFactory
    {
        return PromotionFactory::new();
    }
}
