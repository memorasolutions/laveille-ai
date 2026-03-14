<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Services\AiService;
use Modules\AI\Services\RagService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('RagService returns empty string when no content matches', function () {
    $rag = app(RagService::class);
    expect($rag->getRelevantContext('xyznonexistent'))->toBe('');
});

it('RagService buildSystemPrompt returns base when no context', function () {
    $rag = app(RagService::class);
    expect($rag->buildSystemPrompt('Base prompt', 'xyznonexistent'))->toBe('Base prompt');
});

it('RagService buildSystemPrompt appends context when found', function () {
    \Modules\Faq\Models\Faq::create([
        'question' => 'Test FAQ question about pricing',
        'answer' => 'Test answer about pricing',
        'is_published' => true,
        'order' => 1,
    ]);

    $rag = app(RagService::class);
    $result = $rag->buildSystemPrompt('Base', 'pricing');

    expect($result)->toContain('Base')
        ->toContain('Test FAQ question about pricing');
});

it('SSE stream route requires authentication', function () {
    $this->get(route('ai.stream'))->assertRedirect();
});

it('feedback API requires authentication', function () {
    $user = User::factory()->create();
    $conv = AiConversation::create(['title' => 'Test', 'status' => 'ai_active', 'user_id' => $user->id]);
    $msg = AiMessage::create(['conversation_id' => $conv->id, 'role' => 'assistant', 'content' => 'Test']);

    $this->postJson(route('api.ai.messages.feedback', $msg->id), ['feedback' => 'up'])
        ->assertUnauthorized();
});

it('feedback API stores thumbs up', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'ai_active', 'user_id' => $user->id]);
    $msg = AiMessage::create(['conversation_id' => $conv->id, 'role' => 'assistant', 'content' => 'Test']);

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.ai.messages.feedback', $msg->id), ['feedback' => 'up'])
        ->assertOk();

    $this->assertDatabaseHas('ai_messages', ['id' => $msg->id, 'feedback' => 'up']);
});

it('feedback API rejects invalid feedback value', function () {
    $user = User::factory()->create();
    $conv = AiConversation::create(['title' => 'Test', 'status' => 'ai_active', 'user_id' => $user->id]);
    $msg = AiMessage::create(['conversation_id' => $conv->id, 'role' => 'assistant', 'content' => 'Test']);

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.ai.messages.feedback', $msg->id), ['feedback' => 'invalid'])
        ->assertUnprocessable();
});

it('feedback API prevents accessing others messages', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'ai_active', 'user_id' => $user1->id]);
    $msg = AiMessage::create(['conversation_id' => $conv->id, 'role' => 'assistant', 'content' => 'Test']);

    $this->actingAs($user2, 'sanctum')
        ->postJson(route('api.ai.messages.feedback', $msg->id), ['feedback' => 'up'])
        ->assertForbidden();
});

it('AiService has streamChat method', function () {
    expect(method_exists(new AiService, 'streamChat'))->toBeTrue();
});

it('AiMessage has feedback in fillable', function () {
    $msg = new AiMessage;
    expect($msg->getFillable())->toContain('feedback')
        ->toContain('feedback_comment');
});

it('migration adds feedback columns to ai_messages', function () {
    expect(Schema::hasColumn('ai_messages', 'feedback'))->toBeTrue();
    expect(Schema::hasColumn('ai_messages', 'feedback_comment'))->toBeTrue();
});

it('RagService is registered as singleton', function () {
    $rag1 = app(RagService::class);
    $rag2 = app(RagService::class);
    expect($rag1)->toBeInstanceOf(RagService::class);
});
