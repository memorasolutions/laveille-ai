<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\ProactiveTrigger;

class ProactiveTriggerFactory extends Factory
{
    protected $model = ProactiveTrigger::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'event_type' => $this->faker->randomElement(['page_view', 'idle', 'scroll_bottom', 'cart_abandon', 'first_visit']),
            'conditions' => [],
            'message' => $this->faker->sentence(),
            'is_active' => true,
            'delay_seconds' => $this->faker->randomElement([0, 5, 10, 30, 60]),
        ];
    }
}
