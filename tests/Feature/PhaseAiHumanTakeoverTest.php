<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Events\HumanTakeoverRequested;
use Modules\AI\Livewire\ChatBot;
use Modules\AI\Models\AiConversation;
use Modules\Settings\Models\Setting;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('agent dashboard requires manage_ai permission', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.ai.agent.index'))
        ->assertForbidden();
});

it('agent dashboard shows waiting and active conversations', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    AiConversation::create(['title' => 'Waiting', 'status' => 'waiting_human', 'user_id' => $admin->id]);
    AiConversation::create(['title' => 'Active', 'status' => 'human_active', 'agent_id' => $admin->id, 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.ai.agent.index'))
        ->assertOk()
        ->assertViewHas('waitingConversations')
        ->assertViewHas('myConversations');
});

it('agent can claim waiting conversation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'waiting_human', 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('admin.ai.agent.claim', $conv))
        ->assertRedirect();

    $this->assertDatabaseHas('ai_conversations', [
        'id' => $conv->id,
        'status' => 'human_active',
        'agent_id' => $admin->id,
    ]);
});

it('agent cannot claim non-waiting conversation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'ai_active', 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('admin.ai.agent.claim', $conv))
        ->assertStatus(409);
});

it('agent can reply to own conversation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'human_active', 'agent_id' => $admin->id, 'user_id' => $admin->id]);

    Event::fake();

    $this->actingAs($admin)
        ->post(route('admin.ai.agent.reply', $conv), ['message' => 'Test reply'])
        ->assertRedirect();

    $this->assertDatabaseHas('ai_messages', [
        'conversation_id' => $conv->id,
        'content' => 'Test reply',
        'role' => 'agent',
    ]);
});

it('agent cannot reply to others conversation', function () {
    $admin1 = User::factory()->create();
    $admin1->assignRole('admin');
    $admin2 = User::factory()->create();
    $admin2->assignRole('admin');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'human_active', 'agent_id' => $admin1->id, 'user_id' => $admin1->id]);

    $this->actingAs($admin2)
        ->post(route('admin.ai.agent.reply', $conv), ['message' => 'Unauthorized'])
        ->assertForbidden();
});

it('agent can close conversation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'human_active', 'agent_id' => $admin->id, 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('admin.ai.agent.close', $conv))
        ->assertRedirect();

    $this->assertDatabaseHas('ai_conversations', ['id' => $conv->id, 'status' => 'closed']);
});

it('agent can release conversation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'human_active', 'agent_id' => $admin->id, 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('admin.ai.agent.release', $conv))
        ->assertRedirect();

    $this->assertDatabaseHas('ai_conversations', ['id' => $conv->id, 'status' => 'waiting_human', 'agent_id' => null]);
});

it('chatbot requestHuman changes status to waiting_human', function () {
    Setting::set('ai.chatbot_enabled', true);

    $user = User::factory()->create();
    $user->assignRole('user');

    $conv = AiConversation::create(['title' => 'Chat', 'status' => 'ai_active', 'user_id' => $user->id]);

    Event::fake();

    Livewire::actingAs($user)
        ->test(ChatBot::class)
        ->set('conversationId', $conv->id)
        ->call('requestHuman');

    $this->assertDatabaseHas('ai_conversations', ['id' => $conv->id, 'status' => 'waiting_human']);
    Event::assertDispatched(HumanTakeoverRequested::class);
});
