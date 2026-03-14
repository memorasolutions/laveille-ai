<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Models\WorkflowEnrollment;
use Modules\Newsletter\Models\WorkflowStep;
use Modules\Newsletter\Models\WorkflowStepLog;

class WorkflowEngine
{
    public function enroll(EmailWorkflow $workflow, Subscriber $subscriber): ?WorkflowEnrollment
    {
        if (! $workflow->isActive()) {
            return null;
        }

        $firstStep = $workflow->steps()->orderBy('position')->first();

        return DB::transaction(function () use ($workflow, $subscriber, $firstStep) {
            return WorkflowEnrollment::firstOrCreate(
                [
                    'workflow_id' => $workflow->id,
                    'subscriber_id' => $subscriber->id,
                ],
                [
                    'status' => 'active',
                    'current_step_id' => $firstStep?->id,
                    'enrolled_at' => now(),
                    'next_run_at' => now(),
                ]
            );
        });
    }

    public function processStep(WorkflowEnrollment $enrollment): void
    {
        if (! $enrollment->isActive()) {
            return;
        }

        $step = $enrollment->currentStep;

        if (! $step) {
            $this->complete($enrollment);

            return;
        }

        $log = WorkflowStepLog::create([
            'enrollment_id' => $enrollment->id,
            'step_id' => $step->id,
            'status' => 'waiting',
        ]);

        try {
            match ($step->type) {
                'send_email' => $this->executeSendEmail($enrollment, $step, $log),
                'delay' => $this->executeDelay($enrollment, $step, $log),
                'condition' => $this->executeCondition($enrollment, $step, $log),
                'action' => $this->executeAction($enrollment, $step, $log),
                default => $this->skipStep($enrollment, $log, "Unknown step type: {$step->type}"),
            };
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'executed_at' => now(),
                'metadata' => ['error' => $e->getMessage()],
            ]);

            Log::error('Workflow step failed', [
                'enrollment_id' => $enrollment->id,
                'step_id' => $step->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function advance(WorkflowEnrollment $enrollment): void
    {
        $currentStep = $enrollment->currentStep;

        if (! $currentStep) {
            $this->complete($enrollment);

            return;
        }

        $nextStep = WorkflowStep::where('workflow_id', $enrollment->workflow_id)
            ->where('position', '>', $currentStep->position)
            ->orderBy('position')
            ->first();

        if (! $nextStep) {
            $this->complete($enrollment);

            return;
        }

        $enrollment->update([
            'current_step_id' => $nextStep->id,
            'next_run_at' => now(),
        ]);
    }

    public function cancel(WorkflowEnrollment $enrollment): void
    {
        $enrollment->update([
            'status' => 'cancelled',
            'next_run_at' => null,
        ]);
    }

    public function complete(WorkflowEnrollment $enrollment): void
    {
        $enrollment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'next_run_at' => null,
        ]);
    }

    public function processDueEnrollments(): int
    {
        $enrollments = WorkflowEnrollment::active()
            ->where('next_run_at', '<=', now())
            ->with(['currentStep', 'workflow'])
            ->limit(100)
            ->get();

        $processed = 0;

        foreach ($enrollments as $enrollment) {
            /** @var EmailWorkflow $workflow */
            $workflow = $enrollment->workflow;
            if (! $workflow->isActive()) {
                continue;
            }

            $this->processStep($enrollment);
            $processed++;
        }

        return $processed;
    }

    protected function executeSendEmail(WorkflowEnrollment $enrollment, WorkflowStep $step, WorkflowStepLog $log): void
    {
        /** @var Subscriber $subscriber */
        $subscriber = $enrollment->subscriber;
        /** @var \Modules\Notifications\Models\EmailTemplate|null $template */
        $template = $step->template;

        if (! $template || ! $subscriber->isActive()) {
            $this->skipStep($enrollment, $log, 'No template or inactive subscriber');

            return;
        }

        $subject = $step->config['subject_override'] ?? $template->subject;
        $body = $this->replaceVariables($template->body_html, $subscriber);

        Mail::html($body, function ($message) use ($subscriber, $subject) {
            $message->to($subscriber->email, $subscriber->name)
                ->subject($subject);
        });

        $log->update([
            'status' => 'sent',
            'executed_at' => now(),
            'metadata' => ['email' => $subscriber->email],
        ]);

        $this->advance($enrollment);
    }

    protected function executeDelay(WorkflowEnrollment $enrollment, WorkflowStep $step, WorkflowStepLog $log): void
    {
        $delayHours = (int) ($step->config['delay_hours'] ?? 24);

        $log->update([
            'status' => 'sent',
            'executed_at' => now(),
            'metadata' => ['delay_hours' => $delayHours],
        ]);

        $nextStep = WorkflowStep::where('workflow_id', $enrollment->workflow_id)
            ->where('position', '>', $step->position)
            ->orderBy('position')
            ->first();

        $enrollment->update([
            'current_step_id' => $nextStep?->id,
            'next_run_at' => now()->addHours($delayHours),
        ]);

        if (! $nextStep) {
            $this->complete($enrollment);
        }
    }

    protected function executeCondition(WorkflowEnrollment $enrollment, WorkflowStep $step, WorkflowStepLog $log): void
    {
        $conditionType = $step->config['condition_type'] ?? 'is_active';
        /** @var Subscriber $subscriber */
        $subscriber = $enrollment->subscriber;

        $passed = match ($conditionType) {
            'is_active' => $subscriber->isActive(),
            'is_confirmed' => $subscriber->isConfirmed(),
            default => true,
        };

        $log->update([
            'status' => $passed ? 'sent' : 'skipped',
            'executed_at' => now(),
            'metadata' => ['condition' => $conditionType, 'passed' => $passed],
        ]);

        if ($passed) {
            $this->advance($enrollment);
        } else {
            $this->cancel($enrollment);
        }
    }

    protected function executeAction(WorkflowEnrollment $enrollment, WorkflowStep $step, WorkflowStepLog $log): void
    {
        $log->update([
            'status' => 'sent',
            'executed_at' => now(),
            'metadata' => ['action' => $step->config['action_type'] ?? 'noop'],
        ]);

        $this->advance($enrollment);
    }

    protected function skipStep(WorkflowEnrollment $enrollment, WorkflowStepLog $log, string $reason): void
    {
        $log->update([
            'status' => 'skipped',
            'executed_at' => now(),
            'metadata' => ['reason' => $reason],
        ]);

        $this->advance($enrollment);
    }

    protected function replaceVariables(string $html, Subscriber $subscriber): string
    {
        return str_replace(
            ['{{subscriber.name}}', '{{subscriber.email}}', '{{unsubscribe_url}}'],
            [
                e($subscriber->name ?? 'Subscriber'),
                e($subscriber->email),
                route('newsletter.unsubscribe', ['token' => $subscriber->token]),
            ],
            $html
        );
    }
}
