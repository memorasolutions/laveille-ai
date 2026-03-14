<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Livewire\ChatBot;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Models\CannedReply;
use Modules\AI\Models\InternalNote;
use Modules\Settings\Models\Setting;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// ---------------------------------------------------------------------------
// Agent panel - access control
// ---------------------------------------------------------------------------

it('agent dashboard requires view_ai permission', function () {
    $this->actingAs($this->user)
        ->get(route('admin.ai.agent.index'))
        ->assertForbidden();
});

it('agent dashboard shows waiting conversations', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::WaitingHuman,
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.ai.agent.index'))
        ->assertOk()
        ->assertSee($this->user->name);
});

// ---------------------------------------------------------------------------
// Agent panel - claim / reply / close / release
// ---------------------------------------------------------------------------

it('agent can claim waiting conversation', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::WaitingHuman,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.agent.claim', $conversation))
        ->assertRedirect();

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::HumanActive)
        ->and($conversation->agent_id)->toBe($this->admin->id);
});

it('agent cannot claim non-waiting conversation', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::HumanActive,
        'agent_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.agent.claim', $conversation))
        ->assertStatus(409);
});

it('agent can reply to claimed conversation', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::HumanActive,
        'agent_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.agent.reply', $conversation), [
            'message' => 'Agent reply message',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ai_messages', [
        'conversation_id' => $conversation->id,
        'role' => MessageRole::Agent->value,
        'content' => 'Agent reply message',
    ]);
});

it('agent can close conversation', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::HumanActive,
        'agent_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.agent.close', $conversation))
        ->assertRedirect();

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::Closed)
        ->and($conversation->closed_at)->not->toBeNull();
});

it('agent can release conversation', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::HumanActive,
        'agent_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.agent.release', $conversation))
        ->assertRedirect();

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::WaitingHuman)
        ->and($conversation->agent_id)->toBeNull();
});

it('agent show loads conversation with notes', function () {
    $conversation = AiConversation::factory()->create();
    InternalNote::factory()->create([
        'conversation_id' => $conversation->id,
        'content' => 'Test internal note',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.ai.agent.show', $conversation))
        ->assertOk()
        ->assertSee('Test internal note');
});

// ---------------------------------------------------------------------------
// Canned replies CRUD
// ---------------------------------------------------------------------------

it('canned replies index requires manage_ai', function () {
    $this->actingAs($this->user)
        ->get(route('admin.ai.canned-replies.index'))
        ->assertForbidden();
});

it('admin can create canned reply', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.ai.canned-replies.store'), [
            'title' => 'Test Canned Reply',
            'content' => 'This is the reply content',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('canned_replies', [
        'title' => 'Test Canned Reply',
        'content' => 'This is the reply content',
    ]);
});

it('admin can update canned reply', function () {
    $reply = CannedReply::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.ai.canned-replies.update', $reply), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ])
        ->assertRedirect();

    $reply->refresh();
    expect($reply->title)->toBe('Updated Title')
        ->and($reply->content)->toBe('Updated content');
});

it('admin can delete canned reply', function () {
    $reply = CannedReply::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.ai.canned-replies.destroy', $reply))
        ->assertRedirect();

    $this->assertDatabaseMissing('canned_replies', ['id' => $reply->id]);
});

// ---------------------------------------------------------------------------
// Handoff - requestHuman
// ---------------------------------------------------------------------------

it('requestHuman changes conversation status to WaitingHuman', function () {
    Setting::set('ai.chatbot_enabled', true);

    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::AiActive,
        'user_id' => $this->user->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(ChatBot::class)
        ->call('requestHuman');

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::WaitingHuman);
});

// ---------------------------------------------------------------------------
// Handoff - auto-escalation
// ---------------------------------------------------------------------------

it('auto escalation triggers after 3 poor AI responses', function () {
    Setting::set('ai.chatbot_enabled', true);
    Setting::set('ai.chatbot_agent_handoff_enabled', true);

    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::AiActive,
        'user_id' => $this->user->id,
    ]);

    // Create 3 short AI responses (< 20 chars)
    for ($i = 0; $i < 3; $i++) {
        AiMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant,
            'content' => 'Erreur.',
        ]);
    }

    Livewire::actingAs($this->user)
        ->test(ChatBot::class)
        ->call('checkAutoEscalation');

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::WaitingHuman);
});

// ---------------------------------------------------------------------------
// Handoff - pollAgentMessages
// ---------------------------------------------------------------------------

it('pollAgentMessages fetches new agent messages', function () {
    Setting::set('ai.chatbot_enabled', true);

    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::HumanActive,
        'agent_id' => $this->admin->id,
        'user_id' => $this->user->id,
    ]);

    AiMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => MessageRole::Agent,
        'content' => 'Hello from agent',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ChatBot::class)
        ->call('pollAgentMessages');

    $messages = $component->get('messages');
    $agentMessages = collect($messages)->where('role', 'agent');
    expect($agentMessages)->toHaveCount(1);
    expect($agentMessages->first()['content'])->toBe('Hello from agent');
});

// ---------------------------------------------------------------------------
// Handoff - pollAgentMessages detects closed conversation
// ---------------------------------------------------------------------------

it('pollAgentMessages detects closed conversation', function () {
    Setting::set('ai.chatbot_enabled', true);

    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::Closed,
        'user_id' => $this->user->id,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ChatBot::class)
        ->set('conversationId', $conversation->id)
        ->call('pollAgentMessages');

    $messages = $component->get('messages');
    $closedMsg = collect($messages)->where('role', 'assistant')
        ->filter(fn ($m) => str_contains($m['content'], 'fermé'));

    expect($closedMsg)->not->toBeEmpty();
    expect($component->get('conversationId'))->toBeNull();
});
