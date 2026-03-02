<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Models\WorkflowEnrollment;
use Modules\Newsletter\Models\WorkflowStep;
use Modules\Newsletter\Models\WorkflowStepLog;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app(TenantService::class)->clear();
});

afterEach(function () {
    app(TenantService::class)->clear();
});

test('can create email workflow with factory', function () {
    $workflow = EmailWorkflow::factory()->create();

    expect($workflow)->toBeInstanceOf(EmailWorkflow::class)
        ->and($workflow->name)->not->toBeEmpty()
        ->and($workflow->trigger_type)->toBeIn(['signup', 'purchase', 'custom_event', 'date_based', 'manual'])
        ->and($workflow->status)->toBe('draft');
});

test('workflow has steps relationship', function () {
    $workflow = EmailWorkflow::factory()->create();
    $step1 = WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'position' => 0]);
    $step2 = WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'position' => 1]);

    $steps = $workflow->steps;

    expect($steps)->toHaveCount(2)
        ->and($steps->first()->id)->toBe($step1->id);
});

test('workflow has enrollments relationship', function () {
    $workflow = EmailWorkflow::factory()->create();
    WorkflowEnrollment::factory()->create(['workflow_id' => $workflow->id]);

    expect($workflow->enrollments)->toHaveCount(1);
});

test('workflow belongs to creator', function () {
    $user = User::factory()->create();
    $workflow = EmailWorkflow::factory()->create(['created_by' => $user->id]);

    expect($workflow->creator->id)->toBe($user->id);
});

test('workflow scopes filter correctly', function () {
    EmailWorkflow::factory()->create(['status' => 'draft']);
    EmailWorkflow::factory()->active()->create();
    EmailWorkflow::factory()->paused()->create();

    expect(EmailWorkflow::draft()->count())->toBe(1)
        ->and(EmailWorkflow::active()->count())->toBe(1);
});

test('workflow step belongs to template', function () {
    $template = EmailTemplate::create([
        'name' => 'Welcome',
        'slug' => 'welcome-test',
        'subject' => 'Welcome!',
        'body_html' => '<p>Hello</p>',
        'variables' => ['name'],
        'module' => 'newsletter',
    ]);

    $step = WorkflowStep::factory()->create(['template_id' => $template->id]);

    expect($step->template->id)->toBe($template->id);
});

test('enrollment tracks subscriber and workflow', function () {
    $subscriber = Subscriber::factory()->create();
    $workflow = EmailWorkflow::factory()->create();

    $enrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'subscriber_id' => $subscriber->id,
    ]);

    expect($enrollment->workflow->id)->toBe($workflow->id)
        ->and($enrollment->subscriber->id)->toBe($subscriber->id)
        ->and($enrollment->isActive())->toBeTrue();
});

test('enrollment unique per workflow and subscriber', function () {
    $subscriber = Subscriber::factory()->create();
    $workflow = EmailWorkflow::factory()->create();

    WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'subscriber_id' => $subscriber->id,
    ]);

    expect(fn () => WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'subscriber_id' => $subscriber->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('step log tracks execution', function () {
    $log = WorkflowStepLog::factory()->sent()->create();

    expect($log->status)->toBe('sent')
        ->and($log->executed_at)->not->toBeNull()
        ->and($log->enrollment)->toBeInstanceOf(WorkflowEnrollment::class)
        ->and($log->step)->toBeInstanceOf(WorkflowStep::class);
});

test('workflow auto assigns tenant', function () {
    $tenant = Tenant::factory()->create();
    app(TenantService::class)->switchTo($tenant);

    $workflow = EmailWorkflow::factory()->create();

    expect($workflow->tenant_id)->toBe($tenant->id);
});

test('workflow scoped to tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    app(TenantService::class)->switchTo($tenant1);
    EmailWorkflow::factory()->create(['name' => 'T1 Workflow']);

    app(TenantService::class)->switchTo($tenant2);
    EmailWorkflow::factory()->create(['name' => 'T2 Workflow']);

    app(TenantService::class)->switchTo($tenant1);
    expect(EmailWorkflow::count())->toBe(1)
        ->and(EmailWorkflow::first()->name)->toBe('T1 Workflow');
});
