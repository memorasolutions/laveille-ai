<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\WorkflowEnrollment;
use Modules\Newsletter\Models\WorkflowStep;
use Modules\Newsletter\Models\WorkflowStepLog;

class WorkflowStepLogFactory extends Factory
{
    protected $model = WorkflowStepLog::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => WorkflowEnrollment::factory(),
            'step_id' => WorkflowStep::factory(),
            'status' => 'waiting',
            'metadata' => [],
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => 'sent',
            'executed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'executed_at' => now(),
            'metadata' => ['error' => 'Delivery failed'],
        ]);
    }
}
