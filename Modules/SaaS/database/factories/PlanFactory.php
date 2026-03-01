<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SaaS\Models\Plan;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 0, 299),
            'currency' => 'cad',
            'interval' => $this->faker->randomElement(['monthly', 'yearly']),
            'trial_days' => 0,
            'features' => ['feature_1', 'feature_2'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function monthly(): static
    {
        return $this->state(['interval' => 'monthly']);
    }

    public function yearly(): static
    {
        return $this->state(['interval' => 'yearly']);
    }

    public function free(): static
    {
        return $this->state(['price' => 0, 'name' => 'Free', 'slug' => 'free']);
    }

    public function withTrial(int $days = 14): static
    {
        return $this->state(['trial_days' => $days]);
    }
}
