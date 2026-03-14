<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;
use App\Models\User;
use Modules\AI\Models\ProactiveTrigger;
use Modules\AI\Models\CsatSurvey;
use Modules\AI\Models\Ticket;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// --- Proactive triggers CRUD ---

it('guest redirect login on proactive-triggers.index', function () {
    $this->get(route('admin.ai.proactive-triggers.index'))
        ->assertRedirect(route('login'));
});

it('user gets 403 on proactive-triggers.index', function () {
    $this->actingAs($this->user)
        ->get(route('admin.ai.proactive-triggers.index'))
        ->assertForbidden();
});

it('admin can list proactive triggers', function () {
    ProactiveTrigger::factory()->count(2)->create();
    $this->actingAs($this->admin)
        ->get(route('admin.ai.proactive-triggers.index'))
        ->assertOk();
});

it('admin can create trigger', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.ai.proactive-triggers.store'), [
            'name' => 'Test Trigger',
            'event_type' => 'page_view',
            'message' => 'Bienvenue !',
            'is_active' => true,
            'delay_seconds' => 5,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('proactive_triggers', ['name' => 'Test Trigger']);
});

it('admin can update trigger', function () {
    $trigger = ProactiveTrigger::factory()->create(['name' => 'Old Name']);

    $this->actingAs($this->admin)
        ->put(route('admin.ai.proactive-triggers.update', $trigger), [
            'name' => 'New Name',
            'event_type' => $trigger->event_type,
            'message' => $trigger->message,
            'is_active' => $trigger->is_active,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('proactive_triggers', ['id' => $trigger->id, 'name' => 'New Name']);
});

it('admin can toggle trigger', function () {
    $trigger = ProactiveTrigger::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.ai.proactive-triggers.toggle', $trigger))
        ->assertRedirect();

    expect($trigger->fresh()->is_active)->toBeFalse();
});

it('admin can delete trigger', function () {
    $trigger = ProactiveTrigger::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.ai.proactive-triggers.destroy', $trigger))
        ->assertRedirect();

    $this->assertDatabaseMissing('proactive_triggers', ['id' => $trigger->id]);
});

it('public check endpoint returns matching triggers', function () {
    ProactiveTrigger::factory()->create([
        'event_type' => 'page_view',
        'is_active' => true,
        'conditions' => [],
    ]);
    ProactiveTrigger::factory()->create([
        'event_type' => 'idle',
        'is_active' => true,
    ]);

    $this->postJson(route('ai.proactive-triggers.check'), [
        'event_type' => 'page_view',
        'context' => [],
    ])
        ->assertOk()
        ->assertJsonCount(1);
});

// --- CSAT Surveys ---

it('guest redirect login on csat.index', function () {
    $this->get(route('admin.ai.csat.index'))
        ->assertRedirect(route('login'));
});

it('admin can list CSAT surveys', function () {
    CsatSurvey::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.ai.csat.index'))
        ->assertOk();
});

it('admin can delete CSAT survey', function () {
    $survey = CsatSurvey::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.ai.csat.destroy', $survey))
        ->assertRedirect();

    $this->assertDatabaseMissing('csat_surveys', ['id' => $survey->id]);
});

it('authenticated user can submit CSAT', function () {
    $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson(route('ai.csat.submit'), [
            'ticket_id' => $ticket->id,
            'score' => 5,
            'comment' => 'Great support!',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('csat_surveys', [
        'ticket_id' => $ticket->id,
        'score' => 5,
    ]);
});

it('CSAT submit validates score range', function () {
    $this->actingAs($this->user)
        ->postJson(route('ai.csat.submit'), [
            'score' => 6,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['score']);
});

// --- ProactiveTrigger model ---

it('matchesContext returns true when conditions empty', function () {
    $trigger = new ProactiveTrigger(['conditions' => []]);
    expect($trigger->matchesContext([]))->toBeTrue();
});

it('matchesContext returns true when context matches', function () {
    $trigger = new ProactiveTrigger(['conditions' => ['page' => 'home']]);
    expect($trigger->matchesContext(['page' => 'home']))->toBeTrue();
});

it('matchesContext returns false when context doesnt match', function () {
    $trigger = new ProactiveTrigger(['conditions' => ['page' => 'pricing']]);
    expect($trigger->matchesContext(['page' => 'home']))->toBeFalse();
});

it('scopes active and forEvent work correctly', function () {
    ProactiveTrigger::factory()->create(['is_active' => true, 'event_type' => 'foo']);
    ProactiveTrigger::factory()->create(['is_active' => false, 'event_type' => 'foo']);
    ProactiveTrigger::factory()->create(['is_active' => true, 'event_type' => 'bar']);

    expect(ProactiveTrigger::active()->count())->toBe(2)
        ->and(ProactiveTrigger::forEvent('foo')->count())->toBe(2)
        ->and(ProactiveTrigger::active()->forEvent('foo')->count())->toBe(1);
});

// --- CsatSurvey model ---

it('averageScore returns correct value', function () {
    CsatSurvey::factory()->create(['score' => 4]);
    CsatSurvey::factory()->create(['score' => 5]);
    CsatSurvey::factory()->create(['score' => 3]);

    $avg = CsatSurvey::averageScore();
    expect($avg)->toBe(4.0);
});
