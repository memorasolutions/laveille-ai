<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Models\WorkflowEnrollment;

class WorkflowEnrollmentFactory extends Factory
{
    protected $model = WorkflowEnrollment::class;

    public function definition(): array
    {
        return [
            'workflow_id' => EmailWorkflow::factory(),
            'subscriber_id' => Subscriber::factory(),
            'status' => 'active',
            'enrolled_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
