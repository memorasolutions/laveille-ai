<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));

    $this->user = User::factory()->create();
});

// --- Authentification et permissions ---

it('redirige les non-authentifiés pour les conversations AI', function () {
    $this->get('/admin/ai/conversations')->assertRedirect();
});

it('interdit les conversations AI à un utilisateur sans permission', function () {
    $this->actingAs($this->user)
        ->get('/admin/ai/conversations')
        ->assertForbidden();
});

it('redirige les non-authentifiés pour le dashboard agent', function () {
    $this->get('/admin/ai/agent-dashboard')->assertRedirect();
});

it('interdit le dashboard agent à un utilisateur sans permission', function () {
    $this->actingAs($this->user)
        ->get('/admin/ai/agent-dashboard')
        ->assertForbidden();
});

it('redirige les non-authentifiés pour les analytiques AI', function () {
    $this->get('/admin/ai/analytics')->assertRedirect();
});

it('interdit les analytiques AI à un utilisateur sans permission', function () {
    $this->actingAs($this->user)
        ->get('/admin/ai/analytics')
        ->assertForbidden();
});

// --- Pages admin chargent correctement ---

it('affiche la liste des conversations AI pour un admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/ai/conversations')
        ->assertOk();
});

it('affiche une conversation AI spécifique pour un admin', function () {
    $conversation = AiConversation::factory()->create(['status' => ConversationStatus::AiActive]);

    $this->actingAs($this->admin)
        ->get("/admin/ai/conversations/{$conversation->id}")
        ->assertOk();
});

it('affiche le dashboard agent pour un admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/ai/agent-dashboard')
        ->assertOk();
});

it('affiche les analytiques AI pour un admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/ai/analytics')
        ->assertOk();
});

// --- Opérations CRUD conversations ---

it('ferme une conversation via destroy', function () {
    $conversation = AiConversation::factory()->create(['status' => ConversationStatus::AiActive]);

    $this->actingAs($this->admin)
        ->delete("/admin/ai/conversations/{$conversation->id}")
        ->assertRedirect();

    expect($conversation->fresh()->status)->toBe(ConversationStatus::Closed);
});

it('réclame une conversation en attente humaine', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::WaitingHuman,
        'agent_id' => null,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/ai/agent-dashboard/claim/{$conversation->id}")
        ->assertRedirect();

    $fresh = $conversation->fresh();
    expect($fresh->status)->toBe(ConversationStatus::HumanActive)
        ->and($fresh->agent_id)->toBe($this->admin->id);
});

it('ferme une conversation depuis le dashboard agent', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::HumanActive,
        'agent_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/ai/agent-dashboard/close/{$conversation->id}")
        ->assertRedirect();

    expect($conversation->fresh()->status)->toBe(ConversationStatus::Closed);
});

it('libère une conversation réclamée', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::HumanActive,
        'agent_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/ai/agent-dashboard/release/{$conversation->id}")
        ->assertRedirect();

    $fresh = $conversation->fresh();
    expect($fresh->status)->toBe(ConversationStatus::WaitingHuman)
        ->and($fresh->agent_id)->toBeNull();
});

// --- API Feedback ---

it('retourne 401 pour le feedback sans authentification', function () {
    $message = AiMessage::factory()->create();

    $this->postJson("/api/ai/messages/{$message->id}/feedback", [
        'feedback' => 'up',
    ])->assertUnauthorized();
});

it('interdit le feedback sur le message d un autre utilisateur', function () {
    $otherUser = User::factory()->create();
    $conversation = AiConversation::factory()->create(['user_id' => $otherUser->id]);
    $message = AiMessage::factory()->create(['conversation_id' => $conversation->id]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/ai/messages/{$message->id}/feedback", [
            'feedback' => 'up',
        ])->assertForbidden();
});

it('permet le feedback positif sur son propre message', function () {
    $conversation = AiConversation::factory()->create(['user_id' => $this->user->id]);
    $message = AiMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => MessageRole::Assistant,
    ]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/ai/messages/{$message->id}/feedback", [
            'feedback' => 'up',
        ])->assertSuccessful();

    expect($message->fresh()->feedback)->toBe('up');
});

it('permet le feedback négatif avec commentaire', function () {
    $conversation = AiConversation::factory()->create(['user_id' => $this->user->id]);
    $message = AiMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => MessageRole::Assistant,
    ]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/ai/messages/{$message->id}/feedback", [
            'feedback' => 'down',
            'comment' => 'Réponse incorrecte.',
        ])->assertSuccessful();

    $fresh = $message->fresh();
    expect($fresh->feedback)->toBe('down')
        ->and($fresh->feedback_comment)->toBe('Réponse incorrecte.');
});

it('rejette un feedback invalide', function () {
    $conversation = AiConversation::factory()->create(['user_id' => $this->user->id]);
    $message = AiMessage::factory()->create(['conversation_id' => $conversation->id]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/ai/messages/{$message->id}/feedback", [
            'feedback' => 'invalid',
        ])->assertUnprocessable();
});

it('rejette un commentaire feedback trop long', function () {
    $conversation = AiConversation::factory()->create(['user_id' => $this->user->id]);
    $message = AiMessage::factory()->create(['conversation_id' => $conversation->id]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/ai/messages/{$message->id}/feedback", [
            'feedback' => 'down',
            'comment' => str_repeat('A', 501),
        ])->assertUnprocessable();
});
