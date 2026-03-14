<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ABTest\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ABTest\Models\ABParticipation;
use Modules\ABTest\Models\Experiment;

class ABParticipationFactory extends Factory
{
    protected $model = ABParticipation::class;

    public function definition(): array
    {
        return [
            'experiment_id' => Experiment::factory(),
            'user_id' => null,
            'session_id' => fake()->uuid(),
            'variant' => fake()->randomElement(['A', 'B']),
            'converted_at' => null,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn () => ['converted_at' => now()]);
    }
}
