<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ---------------------------------------------------------------------------
// Model : AiConversation
// ---------------------------------------------------------------------------

it('creates a conversation with uuid', function () {
    $conversation = AiConversation::factory()->create([
        'uuid' => 'a1b2c3d4-0000-0000-0000-000000000001',
    ]);

    expect($conversation->uuid)->toBe('a1b2c3d4-0000-0000-0000-000000000001');
    $this->assertDatabaseHas('ai_conversations', ['uuid' => 'a1b2c3d4-0000-0000-0000-000000000001']);
});

it('auto-generates uuid on creation when none provided', function () {
    $user = User::factory()->create();

    $conversation = AiConversation::create([
        'user_id' => $user->id,
        'title' => 'Auto UUID test',
        'status' => ConversationStatus::AiActive,
        'model' => 'gpt-4o-mini',
    ]);

    expect($conversation->uuid)
        ->not->toBeNull()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('conversation has many messages', function () {
    $conversation = AiConversation::factory()->create();

    AiMessage::factory()->count(3)->create(['conversation_id' => $conversation->id]);

    expect($conversation->messages()->count())->toBe(3);
    expect($conversation->messages->first())->toBeInstanceOf(AiMessage::class);
});

it('conversation belongs to user', function () {
    $user = User::factory()->create();
    $conversation = AiConversation::factory()->create(['user_id' => $user->id]);

    expect($conversation->user)->toBeInstanceOf(User::class);
    expect($conversation->user->id)->toBe($user->id);
});

it('scope active excludes closed conversations', function () {
    AiConversation::factory()->create(['status' => ConversationStatus::AiActive]);
    AiConversation::factory()->create(['status' => ConversationStatus::WaitingHuman]);
    AiConversation::factory()->create(['status' => ConversationStatus::HumanActive]);
    AiConversation::factory()->create(['status' => ConversationStatus::Closed]);

    $active = AiConversation::active()->get();

    expect($active)->toHaveCount(3);
    expect($active->pluck('status')->map->value->toArray())->not->toContain('closed');
});

it('scope by user filters correctly', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    AiConversation::factory()->count(2)->create(['user_id' => $userA->id]);
    AiConversation::factory()->count(3)->create(['user_id' => $userB->id]);

    $result = AiConversation::byUser($userA->id)->get();

    expect($result)->toHaveCount(2);
    expect($result->pluck('user_id')->unique()->first())->toBe($userA->id);
});

it('scope by status filters correctly', function () {
    AiConversation::factory()->count(2)->create(['status' => ConversationStatus::AiActive]);
    AiConversation::factory()->count(1)->create(['status' => ConversationStatus::Closed]);

    $result = AiConversation::byStatus(ConversationStatus::AiActive)->get();

    expect($result)->toHaveCount(2);
    $result->each(fn ($c) => expect($c->status)->toBe(ConversationStatus::AiActive));
});

it('status is cast to ConversationStatus enum', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::WaitingHuman,
    ]);

    $fresh = AiConversation::find($conversation->id);

    expect($fresh->status)->toBeInstanceOf(ConversationStatus::class);
    expect($fresh->status)->toBe(ConversationStatus::WaitingHuman);
});

// ---------------------------------------------------------------------------
// Model : AiMessage
// ---------------------------------------------------------------------------

it('message belongs to conversation', function () {
    $conversation = AiConversation::factory()->create();
    $message = AiMessage::factory()->create(['conversation_id' => $conversation->id]);

    expect($message->conversation)->toBeInstanceOf(AiConversation::class);
    expect($message->conversation->id)->toBe($conversation->id);
});

it('message casts role to MessageRole enum', function () {
    $conversation = AiConversation::factory()->create();

    $message = AiMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => MessageRole::Assistant,
        'content' => 'Voici ma réponse.',
    ]);

    $fresh = AiMessage::find($message->id);

    expect($fresh->role)->toBeInstanceOf(MessageRole::class);
    expect($fresh->role)->toBe(MessageRole::Assistant);
});

it('message has no updated_at column', function () {
    $conversation = AiConversation::factory()->create();
    $message = AiMessage::factory()->create(['conversation_id' => $conversation->id]);

    expect(AiMessage::UPDATED_AT)->toBeNull();
    expect(array_key_exists('updated_at', $message->getAttributes()))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Controller : access control
// ---------------------------------------------------------------------------

it('admin index requires authentication', function () {
    $this->get(route('admin.ai.conversations.index'))
        ->assertRedirect(route('login'));
});

it('admin index requires view_ai permission', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.ai.conversations.index'))
        ->assertForbidden();
});

it('admin index lists conversations with pagination', function () {
    AiConversation::factory()->count(5)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.ai.conversations.index'))
        ->assertOk()
        ->assertViewHas('conversations');
});

it('admin index returns paginated results with 20 per page', function () {
    AiConversation::factory()->count(25)->create();

    $response = $this->actingAs($this->admin)
        ->get(route('admin.ai.conversations.index'));

    $response->assertOk();
    $conversations = $response->viewData('conversations');
    expect($conversations->perPage())->toBe(20);
    expect($conversations->total())->toBe(25);
});

it('admin show loads conversation with messages', function () {
    $conversation = AiConversation::factory()->create(['title' => 'Conversation de test']);
    AiMessage::factory()->count(3)->create(['conversation_id' => $conversation->id]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.ai.conversations.show', $conversation));

    $response->assertOk();
    $response->assertSee('Conversation de test');
    $response->assertViewHas('conversation', function (AiConversation $c) use ($conversation) {
        return $c->id === $conversation->id && $c->relationLoaded('messages');
    });
});

it('admin show requires view_ai permission', function () {
    $conversation = AiConversation::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.ai.conversations.show', $conversation))
        ->assertForbidden();
});

it('admin destroy closes conversation by setting status to closed', function () {
    $conversation = AiConversation::factory()->create([
        'status' => ConversationStatus::AiActive,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.ai.conversations.destroy', $conversation))
        ->assertRedirect(route('admin.ai.conversations.index'))
        ->assertSessionHas('success');

    $conversation->refresh();

    expect($conversation->status)->toBe(ConversationStatus::Closed);
    expect($conversation->closed_at)->not->toBeNull();
});

it('admin destroy requires manage_ai permission', function () {
    $conversation = AiConversation::factory()->create();
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)
        ->delete(route('admin.ai.conversations.destroy', $conversation))
        ->assertForbidden();

    $conversation->refresh();
    expect($conversation->status)->not->toBe(ConversationStatus::Closed);
});

it('admin index filters by status query parameter', function () {
    AiConversation::factory()->count(2)->create(['status' => ConversationStatus::AiActive]);
    AiConversation::factory()->count(3)->create(['status' => ConversationStatus::Closed]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.ai.conversations.index', ['status' => 'closed']));

    $response->assertOk();
    $conversations = $response->viewData('conversations');
    expect($conversations->total())->toBe(3);
});

it('admin index provides status counts for all statuses', function () {
    AiConversation::factory()->count(2)->create(['status' => ConversationStatus::AiActive]);
    AiConversation::factory()->count(1)->create(['status' => ConversationStatus::Closed]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.ai.conversations.index'));

    $response->assertOk();
    $statusCounts = $response->viewData('statusCounts');

    expect($statusCounts)->toBeArray();
    expect(array_key_exists('ai_active', $statusCounts))->toBeTrue();
    expect(array_key_exists('closed', $statusCounts))->toBeTrue();
    expect($statusCounts['ai_active'])->toBe(2);
    expect($statusCounts['closed'])->toBe(1);
});
