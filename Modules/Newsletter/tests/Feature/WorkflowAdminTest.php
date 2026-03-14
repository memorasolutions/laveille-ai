<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\WorkflowStep;
use Modules\Notifications\Models\EmailTemplate;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function workflowAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

test('workflows index loads for admin', function () {
    EmailWorkflow::factory()->create();

    $this->actingAs(workflowAdmin())
        ->get(route('admin.newsletter.workflows.index'))
        ->assertOk()
        ->assertSee('Workflows email');
});

test('workflows index shows empty state', function () {
    $this->actingAs(workflowAdmin())
        ->get(route('admin.newsletter.workflows.index'))
        ->assertOk()
        ->assertSee('Aucun workflow');
});

test('create workflow page loads', function () {
    $this->actingAs(workflowAdmin())
        ->get(route('admin.newsletter.workflows.create'))
        ->assertOk()
        ->assertSee('Nouveau workflow');
});

test('store creates workflow with steps', function () {
    $template = EmailTemplate::create([
        'name' => 'Test Template',
        'slug' => 'test-wf-'.uniqid(),
        'subject' => 'Test',
        'body_html' => '<p>Test</p>',
        'variables' => [],
        'module' => 'newsletter',
    ]);

    $this->actingAs(workflowAdmin())
        ->post(route('admin.newsletter.workflows.store'), [
            'name' => 'Welcome Series',
            'description' => 'A welcome drip campaign',
            'trigger_type' => 'signup',
            'steps' => [
                ['type' => 'send_email', 'template_id' => $template->id],
                ['type' => 'delay', 'config' => ['delay_hours' => 48]],
            ],
        ])
        ->assertRedirect(route('admin.newsletter.workflows.index'));

    $this->assertDatabaseHas('email_workflows', ['name' => 'Welcome Series', 'status' => 'draft']);
    expect(WorkflowStep::count())->toBe(2);
});

test('store validates required fields', function () {
    $this->actingAs(workflowAdmin())
        ->post(route('admin.newsletter.workflows.store'), [])
        ->assertSessionHasErrors(['name', 'trigger_type']);
});

test('show displays workflow analytics', function () {
    $workflow = EmailWorkflow::factory()->create(['name' => 'Welcome Drip']);
    WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'type' => 'send_email']);

    $this->actingAs(workflowAdmin())
        ->get(route('admin.newsletter.workflows.show', $workflow))
        ->assertOk()
        ->assertSee('Actifs')
        ->assertSee('Envoyer email');
});

test('edit workflow page loads', function () {
    $workflow = EmailWorkflow::factory()->create();

    $this->actingAs(workflowAdmin())
        ->get(route('admin.newsletter.workflows.edit', $workflow))
        ->assertOk()
        ->assertSee($workflow->name);
});

test('update modifies workflow', function () {
    $workflow = EmailWorkflow::factory()->create();

    $this->actingAs(workflowAdmin())
        ->put(route('admin.newsletter.workflows.update', $workflow), [
            'name' => 'Updated Workflow',
            'trigger_type' => 'purchase',
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.newsletter.workflows.index'));

    $workflow->refresh();
    expect($workflow->name)->toBe('Updated Workflow')
        ->and($workflow->status)->toBe('active');
});

test('destroy deletes workflow', function () {
    $workflow = EmailWorkflow::factory()->create();

    $this->actingAs(workflowAdmin())
        ->delete(route('admin.newsletter.workflows.destroy', $workflow))
        ->assertRedirect(route('admin.newsletter.workflows.index'));

    $this->assertDatabaseMissing('email_workflows', ['id' => $workflow->id]);
});

test('activate sets workflow active', function () {
    $workflow = EmailWorkflow::factory()->create(['status' => 'draft']);

    $this->actingAs(workflowAdmin())
        ->post(route('admin.newsletter.workflows.activate', $workflow))
        ->assertRedirect();

    expect($workflow->fresh()->status)->toBe('active');
});

test('pause sets workflow paused', function () {
    $workflow = EmailWorkflow::factory()->active()->create();

    $this->actingAs(workflowAdmin())
        ->post(route('admin.newsletter.workflows.pause', $workflow))
        ->assertRedirect();

    expect($workflow->fresh()->status)->toBe('paused');
});

test('non-admin gets 403 on workflows', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.newsletter.workflows.index'))
        ->assertForbidden();
});
