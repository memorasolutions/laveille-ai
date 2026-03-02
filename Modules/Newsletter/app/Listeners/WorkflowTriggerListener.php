<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Services\WorkflowEngine;

class WorkflowTriggerListener
{
    public function __construct(
        protected WorkflowEngine $engine
    ) {}

    public function handleRegistered(Registered $event): void
    {
        $this->triggerWorkflows('signup', $event->user->email);
    }

    public function triggerWorkflows(string $triggerType, ?string $email = null): void
    {
        $workflows = EmailWorkflow::active()
            ->where('trigger_type', $triggerType)
            ->get();

        if ($workflows->isEmpty() || ! $email) {
            return;
        }

        $subscriber = Subscriber::where('email', $email)->first();

        if (! $subscriber || ! $subscriber->isActive()) {
            return;
        }

        foreach ($workflows as $workflow) {
            try {
                $this->engine->enroll($workflow, $subscriber);
            } catch (\Throwable $e) {
                Log::error('Workflow enrollment failed', [
                    'workflow_id' => $workflow->id,
                    'subscriber_email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
