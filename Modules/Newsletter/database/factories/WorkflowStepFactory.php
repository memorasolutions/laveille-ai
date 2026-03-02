<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\WorkflowStep;

class WorkflowStepFactory extends Factory
{
    protected $model = WorkflowStep::class;

    public function definition(): array
    {
        return [
            'workflow_id' => EmailWorkflow::factory(),
            'type' => fake()->randomElement(['send_email', 'delay', 'condition', 'action']),
            'config' => [],
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    public function sendEmail(): static
    {
        return $this->state(fn () => [
            'type' => 'send_email',
            'config' => ['subject_override' => null],
        ]);
    }

    public function delay(int $hours = 24): static
    {
        return $this->state(fn () => [
            'type' => 'delay',
            'config' => ['delay_hours' => $hours],
        ]);
    }
}
