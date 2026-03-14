<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Models\WorkflowEnrollment;
use Modules\Newsletter\Models\WorkflowStep;
use Modules\Newsletter\Models\WorkflowStepLog;
use Modules\Newsletter\Services\WorkflowEngine;
use Modules\Notifications\Models\EmailTemplate;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function createWorkflowEngine(): WorkflowEngine
{
    return app(WorkflowEngine::class);
}

function createActiveWorkflowWithSteps(): array
{
    $template = EmailTemplate::create([
        'name' => 'Welcome Email',
        'slug' => 'welcome-wf-'.uniqid(),
        'subject' => 'Welcome!',
        'body_html' => '<p>Hello {{subscriber.name}}</p>',
        'variables' => ['name'],
        'module' => 'newsletter',
    ]);

    $workflow = EmailWorkflow::factory()->active()->create();

    $step1 = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'template_id' => $template->id,
        'position' => 0,
    ]);

    $step2 = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'delay',
        'config' => ['delay_hours' => 24],
        'position' => 1,
    ]);

    $step3 = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'template_id' => $template->id,
        'position' => 2,
    ]);

    return [$workflow, $step1, $step2, $step3, $template];
}

test('enroll creates enrollment for active workflow', function () {
    $engine = createWorkflowEngine();
    $workflow = EmailWorkflow::factory()->active()->create();
    WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'position' => 0]);
    $subscriber = Subscriber::factory()->create(['confirmed_at' => now()]);

    $enrollment = $engine->enroll($workflow, $subscriber);

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->status)->toBe('active')
        ->and($enrollment->workflow_id)->toBe($workflow->id)
        ->and($enrollment->subscriber_id)->toBe($subscriber->id);
});

test('enroll returns null for draft workflow', function () {
    $engine = createWorkflowEngine();
    $workflow = EmailWorkflow::factory()->create(['status' => 'draft']);
    $subscriber = Subscriber::factory()->create();

    expect($engine->enroll($workflow, $subscriber))->toBeNull();
});

test('enroll does not duplicate enrollment', function () {
    $engine = createWorkflowEngine();
    $workflow = EmailWorkflow::factory()->active()->create();
    WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'position' => 0]);
    $subscriber = Subscriber::factory()->create(['confirmed_at' => now()]);

    $e1 = $engine->enroll($workflow, $subscriber);
    $e2 = $engine->enroll($workflow, $subscriber);

    expect($e1->id)->toBe($e2->id)
        ->and(WorkflowEnrollment::count())->toBe(1);
});

test('process step sends email', function () {
    Mail::fake();
    $engine = createWorkflowEngine();
    [$workflow, $step1, , , $template] = createActiveWorkflowWithSteps();
    $subscriber = Subscriber::factory()->create(['confirmed_at' => now(), 'email' => 'test@example.com']);

    $enrollment = $engine->enroll($workflow, $subscriber);
    $engine->processStep($enrollment);

    $enrollment->refresh();
    $log = WorkflowStepLog::where('step_id', $step1->id)->first();
    expect($log->status)->toBe('sent')
        ->and($log->metadata['email'])->toBe('test@example.com')
        ->and($enrollment->current_step_id)->not->toBe($step1->id);
});

test('process delay step sets future next_run_at', function () {
    Mail::fake();
    $engine = createWorkflowEngine();
    [$workflow, $step1, $step2] = createActiveWorkflowWithSteps();
    $subscriber = Subscriber::factory()->create(['confirmed_at' => now()]);

    $enrollment = $engine->enroll($workflow, $subscriber);

    // Process step 1 (send_email)
    $engine->processStep($enrollment);
    $enrollment->refresh();

    // Now process step 2 (delay)
    $engine->processStep($enrollment);
    $enrollment->refresh();

    expect($enrollment->next_run_at)->toBeGreaterThan(now());
});

test('process step completes enrollment at end', function () {
    Mail::fake();
    $engine = createWorkflowEngine();

    $template = EmailTemplate::create([
        'name' => 'Single',
        'slug' => 'single-'.uniqid(),
        'subject' => 'Hi',
        'body_html' => '<p>Hello</p>',
        'variables' => [],
        'module' => 'newsletter',
    ]);

    $workflow = EmailWorkflow::factory()->active()->create();
    WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'template_id' => $template->id,
        'position' => 0,
    ]);

    $subscriber = Subscriber::factory()->create(['confirmed_at' => now()]);
    $enrollment = $engine->enroll($workflow, $subscriber);

    $engine->processStep($enrollment);
    $enrollment->refresh();

    expect($enrollment->status)->toBe('completed')
        ->and($enrollment->completed_at)->not->toBeNull();
});

test('cancel enrollment sets cancelled status', function () {
    $engine = createWorkflowEngine();
    $workflow = EmailWorkflow::factory()->active()->create();
    WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'position' => 0]);
    $subscriber = Subscriber::factory()->create(['confirmed_at' => now()]);

    $enrollment = $engine->enroll($workflow, $subscriber);
    $engine->cancel($enrollment);
    $enrollment->refresh();

    expect($enrollment->status)->toBe('cancelled')
        ->and($enrollment->next_run_at)->toBeNull();
});

test('condition step cancels if subscriber inactive', function () {
    $engine = createWorkflowEngine();
    $workflow = EmailWorkflow::factory()->active()->create();
    WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'condition',
        'config' => ['condition_type' => 'is_active'],
        'position' => 0,
    ]);

    $subscriber = Subscriber::factory()->create([
        'confirmed_at' => now(),
        'unsubscribed_at' => now(),
    ]);

    $enrollment = $engine->enroll($workflow, $subscriber);
    $engine->processStep($enrollment);
    $enrollment->refresh();

    expect($enrollment->status)->toBe('cancelled');
});

test('process due enrollments batch processes', function () {
    Mail::fake();
    $engine = createWorkflowEngine();

    $template = EmailTemplate::create([
        'name' => 'Batch',
        'slug' => 'batch-'.uniqid(),
        'subject' => 'Hi',
        'body_html' => '<p>Hi</p>',
        'variables' => [],
        'module' => 'newsletter',
    ]);

    $workflow = EmailWorkflow::factory()->active()->create();
    WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'template_id' => $template->id,
        'position' => 0,
    ]);

    $s1 = Subscriber::factory()->create(['confirmed_at' => now()]);
    $s2 = Subscriber::factory()->create(['confirmed_at' => now()]);

    $engine->enroll($workflow, $s1);
    $engine->enroll($workflow, $s2);

    $processed = $engine->processDueEnrollments();

    expect($processed)->toBe(2)
        ->and(WorkflowStepLog::where('status', 'sent')->count())->toBe(2);
});

test('process workflows command runs', function () {
    $this->artisan('newsletter:process-workflows')
        ->expectsOutputToContain('Processed')
        ->assertSuccessful();
});

test('failed step logs error metadata', function () {
    $engine = createWorkflowEngine();
    $workflow = EmailWorkflow::factory()->active()->create();
    WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'send_email',
        'template_id' => null,
        'position' => 0,
    ]);

    $subscriber = Subscriber::factory()->create(['confirmed_at' => now()]);
    $enrollment = $engine->enroll($workflow, $subscriber);

    $engine->processStep($enrollment);

    $log = WorkflowStepLog::where('enrollment_id', $enrollment->id)->first();
    expect($log->status)->toBe('skipped');
});
