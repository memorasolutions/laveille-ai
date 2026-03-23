<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

use App\Models\User;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Enums\TicketPriority;
use Modules\AI\Enums\TicketStatus;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Models\SlaPolicy;
use Modules\AI\Models\Ticket;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

it('guest ne peut pas accéder aux tickets', function () {
    $this->get(route('admin.ai.tickets.index'))
        ->assertRedirect(route('login'));
});

it('user role reçoit 403 sur tickets index', function () {
    $this->actingAs($this->user)
        ->get(route('admin.ai.tickets.index'))
        ->assertForbidden();
});

it('admin peut lister les tickets', function () {
    Ticket::factory()->count(2)->create(['user_id' => $this->admin->id]);
    $this->actingAs($this->admin)
        ->get(route('admin.ai.tickets.index'))
        ->assertOk();
});

it('admin peut voir le formulaire de création', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.ai.tickets.create'))
        ->assertOk();
});

it('admin peut créer un ticket', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.ai.tickets.store'), [
            'title' => 'Bug critique',
            'description' => 'Quelque chose ne fonctionne pas.',
            'priority' => 'high',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tickets', [
        'title' => 'Bug critique',
        'priority' => TicketPriority::High->value,
    ]);
});

it('admin peut voir le détail d\'un ticket', function () {
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);
    $this->actingAs($this->admin)
        ->get(route('admin.ai.tickets.show', $ticket))
        ->assertOk()
        ->assertSee($ticket->title);
});

it('admin peut mettre à jour statut et priorité', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->admin->id,
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Low,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.ai.tickets.update', $ticket), [
            'status' => 'in_progress',
            'priority' => 'urgent',
        ])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::InProgress);
    expect($ticket->priority)->toBe(TicketPriority::Urgent);
});

it('admin peut répondre à un ticket', function () {
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.tickets.reply', $ticket), [
            'content' => 'Voici une réponse.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_replies', [
        'ticket_id' => $ticket->id,
        'content' => 'Voici une réponse.',
        'is_internal' => false,
    ]);
});

it('la réponse interne est correctement marquée', function () {
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.tickets.reply', $ticket), [
            'content' => 'Note interne',
            'is_internal' => 1,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_replies', [
        'ticket_id' => $ticket->id,
        'content' => 'Note interne',
        'is_internal' => true,
    ]);
});

it('admin peut fermer un ticket', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->admin->id,
        'status' => TicketStatus::InProgress,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.tickets.close', $ticket))
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Closed);
    expect($ticket->closed_at)->not()->toBeNull();
});

it('admin peut résoudre un ticket', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->admin->id,
        'status' => TicketStatus::InProgress,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.tickets.resolve', $ticket))
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Resolved);
    expect($ticket->resolved_at)->not()->toBeNull();
});

it('admin peut créer un ticket depuis une conversation', function () {
    $conv = AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Conversation test',
        'status' => 'ai_active',
    ]);
    AiMessage::create([
        'conversation_id' => $conv->id,
        'role' => MessageRole::User,
        'content' => 'J\'ai un problème.',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.tickets.from-conversation', $conv))
        ->assertRedirect();

    $this->assertDatabaseHas('tickets', [
        'conversation_id' => $conv->id,
        'title' => 'Conversation test',
    ]);
});

it('admin peut lister les politiques SLA', function () {
    SlaPolicy::factory()->count(2)->create();
    $this->actingAs($this->admin)
        ->get(route('admin.ai.sla.index'))
        ->assertOk();
});

it('admin peut créer une politique SLA', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.ai.sla.store'), [
            'name' => 'SLA VIP',
            'priority' => 'urgent',
            'first_response_hours' => 2,
            'resolution_hours' => 6,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('sla_policies', ['name' => 'SLA VIP']);
});

it('admin peut modifier une politique SLA', function () {
    $sla = SlaPolicy::factory()->create(['name' => 'SLA Standard']);

    $this->actingAs($this->admin)
        ->put(route('admin.ai.sla.update', $sla), [
            'name' => 'SLA Modifié',
            'priority' => 'high',
            'first_response_hours' => 4,
            'resolution_hours' => 12,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('sla_policies', ['id' => $sla->id, 'name' => 'SLA Modifié']);
});

it('admin peut supprimer une politique SLA', function () {
    $sla = SlaPolicy::factory()->create(['name' => 'SLA Obsolète']);

    $this->actingAs($this->admin)
        ->delete(route('admin.ai.sla.destroy', $sla))
        ->assertRedirect();

    $this->assertDatabaseMissing('sla_policies', ['id' => $sla->id]);
});
