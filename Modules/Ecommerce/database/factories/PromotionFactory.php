<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Promotion;

class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' promotion',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => fake()->randomFloat(2, 5, 50),
            'conditions' => null,
            'tiers' => null,
            'bogo_config' => null,
            'applies_to' => Promotion::APPLIES_ALL,
            'target_ids' => null,
            'priority' => fake()->numberBetween(0, 10),
            'is_stackable' => false,
            'is_automatic' => true,
            'max_uses' => null,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }

    public function percentage(float $value = 15.0): static
    {
        return $this->state([
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => $value,
        ]);
    }

    public function fixed(float $value = 10.0): static
    {
        return $this->state([
            'type' => Promotion::TYPE_FIXED,
            'value' => $value,
        ]);
    }

    public function bogo(int $buyQty = 2, int $getQty = 1, float $getDiscount = 100): static
    {
        return $this->state([
            'type' => Promotion::TYPE_BOGO,
            'value' => 0,
            'bogo_config' => [
                'buy_qty' => $buyQty,
                'get_qty' => $getQty,
                'get_discount' => $getDiscount,
            ],
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state([
            'type' => Promotion::TYPE_FREE_SHIPPING,
            'value' => 0,
        ]);
    }

    public function tiered(): static
    {
        return $this->state([
            'type' => Promotion::TYPE_TIERED,
            'value' => 0,
            'tiers' => [
                ['min_amount' => 50, 'discount_percent' => 5],
                ['min_amount' => 100, 'discount_percent' => 10],
                ['min_amount' => 200, 'discount_percent' => 15],
            ],
        ]);
    }

    public function withMinOrder(float $amount): static
    {
        return $this->state([
            'conditions' => ['min_order' => $amount],
        ]);
    }

    public function withMinQty(int $qty): static
    {
        return $this->state([
            'conditions' => ['min_qty' => $qty],
        ]);
    }

    public function forProducts(array $productIds): static
    {
        return $this->state([
            'applies_to' => Promotion::APPLIES_PRODUCTS,
            'target_ids' => $productIds,
        ]);
    }

    public function forCategories(array $categoryIds): static
    {
        return $this->state([
            'applies_to' => Promotion::APPLIES_CATEGORIES,
            'target_ids' => $categoryIds,
        ]);
    }

    public function stackable(): static
    {
        return $this->state(['is_stackable' => true]);
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function exhausted(): static
    {
        return $this->state([
            'max_uses' => 10,
            'used_count' => 10,
        ]);
    }
}
