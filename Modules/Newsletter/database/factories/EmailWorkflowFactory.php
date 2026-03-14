<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\EmailWorkflow;

class EmailWorkflowFactory extends Factory
{
    protected $model = EmailWorkflow::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'trigger_type' => fake()->randomElement(['signup', 'purchase', 'custom_event', 'date_based', 'manual']),
            'trigger_config' => [],
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['status' => 'paused']);
    }
}
