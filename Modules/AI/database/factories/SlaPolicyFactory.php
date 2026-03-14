<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Enums\TicketPriority;
use Modules\AI\Models\SlaPolicy;

/** @extends Factory<SlaPolicy> */
class SlaPolicyFactory extends Factory
{
    protected $model = SlaPolicy::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(2),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'first_response_hours' => fake()->randomElement([1, 2, 4, 8]),
            'resolution_hours' => fake()->randomElement([4, 8, 24, 48]),
            'is_active' => true,
        ];
    }
}
