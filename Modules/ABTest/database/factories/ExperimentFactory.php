<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ABTest\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\ABTest\Models\Experiment;

class ExperimentFactory extends Factory
{
    protected $model = Experiment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'variants' => ['control', 'variant_a'],
            'status' => 'draft',
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => 'running',
            'started_at' => now()->subDays(3),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'winner' => 'control',
            'started_at' => now()->subWeek(),
            'ended_at' => now(),
        ]);
    }
}
