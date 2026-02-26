<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Livewire\ChatBot;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole($role);
});

// --- Component Registration ---

it('registers ai-chatbot Livewire component', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(ChatBot::class)
        ->assertStatus(200);
});

// --- Visibility based on setting ---

it('renders nothing when chatbot is disabled', function () {
    Setting::set('ai.chatbot_enabled', '0');

    Livewire::test(ChatBot::class)
        ->assertDontSeeHtml('ai-chatbot-bubble')
        ->assertDontSeeHtml('ai-chatbot-panel');
});

it('renders chat bubble when chatbot is enabled', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(ChatBot::class)
        ->assertSeeHtml('ai-chatbot-bubble');
});

// --- Toggle open/close ---

it('toggles chat panel open and closed', function () {
    Setting::set('ai.chatbot_enabled', '1');

    $component = Livewire::test(ChatBot::class);

    expect($component->get('isOpen'))->toBeFalse();

    $component->call('toggleOpen');
    expect($component->get('isOpen'))->toBeTrue();

    $component->call('toggleOpen');
    expect($component->get('isOpen'))->toBeFalse();
});

// --- Message validation ---

it('validates empty message', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(ChatBot::class)
        ->set('message', '')
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'required']);
});

it('validates message max length', function () {
    Setting::set('ai.chatbot_enabled', '1');

    Livewire::test(ChatBot::class)
        ->set('message', str_repeat('a', 1001))
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'max']);
});

// --- Send message with AI response ---

it('sends a message and receives AI response', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Hello! I can help you.']],
            ],
        ]),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ChatBot::class)
        ->set('message', 'Help me please')
        ->call('sendMessage');

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(2)
        ->and($messages[0]['role'])->toBe('user')
        ->and($messages[0]['content'])->toBe('Help me please')
        ->and($messages[1]['role'])->toBe('assistant')
        ->and($messages[1]['content'])->toBe('Hello! I can help you.');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'openrouter.ai'));
});

// --- Persistence: authenticated user (DB) ---

it('persists messages in database for authenticated users', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'DB response']],
            ],
        ]),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ChatBot::class)
        ->set('message', 'Test DB persist')
        ->call('sendMessage');

    expect(AiConversation::where('user_id', $this->admin->id)->count())->toBe(1)
        ->and(AiMessage::count())->toBe(2);

    $conversation = AiConversation::where('user_id', $this->admin->id)->first();
    expect($conversation->status)->toBe(ConversationStatus::AiActive);

    $messages = $conversation->messages()->orderBy('created_at')->get();
    expect($messages[0]->role->value)->toBe('user')
        ->and($messages[0]->content)->toBe('Test DB persist')
        ->and($messages[1]->role->value)->toBe('assistant')
        ->and($messages[1]->content)->toBe('DB response');
});

// --- Persistence: guest user (session) ---

it('persists messages in session for guest users', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Guest response']],
            ],
        ]),
    ]);

    $component = Livewire::test(ChatBot::class)
        ->set('message', 'Guest question')
        ->call('sendMessage');

    $messages = $component->get('messages');
    expect($messages)->toHaveCount(2)
        ->and($messages[0]['content'])->toBe('Guest question')
        ->and($messages[1]['content'])->toBe('Guest response');

    // No DB records for guests
    expect(AiConversation::count())->toBe(0);
});

// --- API error handling ---

it('handles API errors gracefully', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([], 500),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ChatBot::class)
        ->set('message', 'Will fail')
        ->call('sendMessage');

    expect($component->get('error'))->not->toBeEmpty();
});

it('handles missing API key', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');
    // No API key set

    $component = Livewire::actingAs($this->admin)
        ->test(ChatBot::class)
        ->set('message', 'No key test')
        ->call('sendMessage');

    expect($component->get('error'))->not->toBeEmpty();
});

// --- Clear conversation ---

it('clears conversation for authenticated user', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'To be cleared']],
            ],
        ]),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ChatBot::class)
        ->set('message', 'Clear me')
        ->call('sendMessage')
        ->call('clearConversation');

    expect($component->get('messages'))->toBeEmpty()
        ->and($component->get('conversationId'))->toBeNull();

    $conversation = AiConversation::where('user_id', $this->admin->id)->first();
    expect($conversation->status)->toBe(ConversationStatus::Closed);
});

it('clears conversation for guest user', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Guest clear']],
            ],
        ]),
    ]);

    $component = Livewire::test(ChatBot::class)
        ->set('message', 'Guest clear test')
        ->call('sendMessage')
        ->call('clearConversation');

    expect($component->get('messages'))->toBeEmpty();
});

// --- Loading state ---

it('manages loading state during message send', function () {
    Setting::set('ai.chatbot_enabled', '1');
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.system_prompt', 'You are helpful.');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Done']],
            ],
        ]),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ChatBot::class)
        ->set('message', 'Loading test')
        ->call('sendMessage');

    // After sendMessage completes, isLoading should be false
    expect($component->get('isLoading'))->toBeFalse();
});

// --- Loads existing conversation ---

it('loads existing conversation on mount for authenticated user', function () {
    Setting::set('ai.chatbot_enabled', '1');

    $conversation = AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Existing conv',
        'status' => ConversationStatus::AiActive,
        'model' => 'test-model',
    ]);

    AiMessage::create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Previous question',
    ]);

    AiMessage::create([
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'Previous answer',
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ChatBot::class);

    $messages = $component->get('messages');
    expect($messages)->toHaveCount(2)
        ->and($messages[0]['content'])->toBe('Previous question')
        ->and($messages[1]['content'])->toBe('Previous answer')
        ->and($component->get('conversationId'))->toBe($conversation->id);
});

// --- chatWithHistory method ---

it('AiService chatWithHistory sends multi-turn messages', function () {
    Setting::set('ai.openrouter_api_key', 'test-key');
    Setting::set('ai.chatbot_model', 'test-model');
    Setting::set('ai.temperature', '0.7');
    Setting::set('ai.max_tokens', '2048');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Multi-turn response']],
            ],
        ]),
    ]);

    $service = app(\Modules\AI\Services\AiService::class);

    $result = $service->chatWithHistory([
        ['role' => 'system', 'content' => 'You are helpful.'],
        ['role' => 'user', 'content' => 'First message'],
        ['role' => 'assistant', 'content' => 'First reply'],
        ['role' => 'user', 'content' => 'Follow up'],
    ]);

    expect($result)->toBe('Multi-turn response');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return count($body['messages']) === 4
            && $body['messages'][0]['role'] === 'system';
    });
});

// --- Layout inclusion ---

it('includes chatbot in GoSaaS frontend layout', function () {
    $layoutPath = module_path('FrontTheme', 'resources/views/themes/gosass/layouts/app.blade.php');
    $content = file_get_contents($layoutPath);

    expect($content)->toContain("@livewire('ai-chatbot')");
});

it('includes chatbot in user dashboard layout', function () {
    $layoutPath = module_path('Auth', 'resources/views/layouts/app.blade.php');
    $content = file_get_contents($layoutPath);

    expect($content)->toContain("@livewire('ai-chatbot')");
});

// --- Translations ---

it('has all required chatbot translations', function () {
    $frPath = lang_path('fr.json');
    $enPath = lang_path('en.json');

    $fr = json_decode(file_get_contents($frPath), true);
    $en = json_decode(file_get_contents($enPath), true);

    $keys = [
        'Ouvrir le chatbot',
        'Assistant IA',
        'Fermer le chatbot',
        'Effacer la conversation',
        'Messages du chatbot',
        'Bonjour ! Comment puis-je vous aider ?',
        'Votre message',
        'Écrivez votre message...',
        'Envoyer',
        'Une erreur est survenue. Veuillez réessayer.',
    ];

    foreach ($keys as $key) {
        expect($fr)->toHaveKey($key)
            ->and($en)->toHaveKey($key);
    }
});
