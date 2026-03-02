<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Services\AiService;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('AI module is registered', function () {
    expect(module_path('AI'))->toBeDirectory();

    $statuses = json_decode(file_get_contents(base_path('modules_statuses.json')), true);
    expect($statuses)->toHaveKey('AI', true);
});

test('AiService is a singleton', function () {
    $service1 = resolve(AiService::class);
    $service2 = resolve(AiService::class);

    expect($service1)->toBe($service2);
});

test('AiService::getModelForTask returns correct model for each task', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);
    $service = resolve(AiService::class);

    expect($service->getModelForTask('chatbot'))->toBe(Setting::get('ai.chatbot_model'))
        ->and($service->getModelForTask('content'))->toBe(Setting::get('ai.content_model'))
        ->and($service->getModelForTask('moderation'))->toBe(Setting::get('ai.moderation_model'))
        ->and($service->getModelForTask('seo'))->toBe(Setting::get('ai.seo_model'))
        ->and($service->getModelForTask('translation'))->toBe(Setting::get('ai.default_model'));
});

test('AiConversation creates with UUID auto-generated', function () {
    $conversation = AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Test Conversation',
    ]);

    expect($conversation->uuid)->not->toBeNull()
        ->and(strlen($conversation->uuid))->toBe(36);
});

test('AiConversation has messages relationship', function () {
    $conversation = AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Test',
    ]);

    AiMessage::create([
        'conversation_id' => $conversation->id,
        'role' => MessageRole::User,
        'content' => 'Hello',
    ]);

    expect($conversation->messages)->toHaveCount(1);
});

test('AiMessage belongs to conversation', function () {
    $conversation = AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Test',
    ]);

    $message = AiMessage::create([
        'conversation_id' => $conversation->id,
        'role' => MessageRole::User,
        'content' => 'Hello',
    ]);

    expect($message->conversation->id)->toBe($conversation->id);
});

test('ConversationStatus enum has 4 expected values', function () {
    expect(ConversationStatus::cases())->toHaveCount(4)
        ->and(ConversationStatus::AiActive->value)->toBe('ai_active')
        ->and(ConversationStatus::WaitingHuman->value)->toBe('waiting_human')
        ->and(ConversationStatus::HumanActive->value)->toBe('human_active')
        ->and(ConversationStatus::Closed->value)->toBe('closed');
});

test('MessageRole enum has 4 expected values', function () {
    expect(MessageRole::cases())->toHaveCount(4)
        ->and(MessageRole::System->value)->toBe('system')
        ->and(MessageRole::User->value)->toBe('user')
        ->and(MessageRole::Assistant->value)->toBe('assistant')
        ->and(MessageRole::Agent->value)->toBe('agent');
});

test('AI settings exist after seeding', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    $aiSettings = Setting::where('group', 'ai')->get();
    expect($aiSettings)->toHaveCount(18);
});

test('AiService::chat makes HTTP call', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);
    Setting::where('key', 'ai.openrouter_api_key')->update(['value' => 'test-key-123']);

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Test response']]],
        ], 200),
    ]);

    $service = resolve(AiService::class);
    $result = $service->chat('Hello AI');

    expect($result)->toBe('Test response');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'openrouter.ai/api/v1/chat/completions');
    });
});

test('AiService handles API errors gracefully', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);
    Setting::where('key', 'ai.openrouter_api_key')->update(['value' => 'test-key-123']);

    Http::fake([
        'openrouter.ai/*' => Http::response([], 500),
    ]);

    $service = resolve(AiService::class);
    $result = $service->chat('Hello AI');

    expect($result)->toBe('');
});

test('AiConversation scope active works', function () {
    AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Active',
        'status' => ConversationStatus::AiActive,
    ]);

    AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Closed',
        'status' => ConversationStatus::Closed,
    ]);

    expect(AiConversation::active()->count())->toBe(1);
});

test('AiConversation scope byUser works', function () {
    $user2 = User::factory()->create();

    AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Admin Conversation',
    ]);

    AiConversation::create([
        'user_id' => $user2->id,
        'title' => 'User2 Conversation',
    ]);

    expect(AiConversation::byUser($this->admin->id)->count())->toBe(1);
});

test('AiConversation scope byStatus works', function () {
    AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Active',
        'status' => ConversationStatus::AiActive,
    ]);

    AiConversation::create([
        'user_id' => $this->admin->id,
        'title' => 'Waiting',
        'status' => ConversationStatus::WaitingHuman,
    ]);

    expect(AiConversation::byStatus(ConversationStatus::WaitingHuman)->count())->toBe(1);
});

test('AI settings accessible via Setting::get after seeding', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    expect(Setting::get('ai.openrouter_api_key'))->not->toBeNull()
        ->and(Setting::get('ai.default_model'))->toBe('meta-llama/llama-3.3-70b-instruct:free')
        ->and(Setting::get('ai.chatbot_model'))->toBe('meta-llama/llama-3.3-70b-instruct:free')
        ->and(Setting::get('ai.content_model'))->toBe('qwen/qwen3-coder:free')
        ->and(Setting::get('ai.temperature'))->toBe('0.7')
        ->and(Setting::get('ai.chatbot_enabled'))->toBeFalse();
});
