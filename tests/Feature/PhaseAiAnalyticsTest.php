<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Services\AiService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('analytics route requires authentication', function () {
    $this->get(route('admin.ai.analytics'))->assertRedirect();
});

it('analytics route requires manage_ai permission', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.ai.analytics'))
        ->assertForbidden();
});

it('admin can access analytics page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.ai.analytics'))
        ->assertOk()
        ->assertSee('Analytiques IA');
});

it('analytics page shows KPI data', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $conv = AiConversation::create(['title' => 'Test', 'status' => 'ai_active', 'user_id' => $user->id]);
    AiMessage::create(['conversation_id' => $conv->id, 'role' => 'user', 'content' => 'Hello']);
    AiMessage::create(['conversation_id' => $conv->id, 'role' => 'assistant', 'content' => 'Hi', 'model' => 'test-model']);

    $this->actingAs($user)
        ->get(route('admin.ai.analytics'))
        ->assertOk()
        ->assertSee('Conversations totales')
        ->assertSee('Messages totaux');
});

it('AiService has checkBudget method', function () {
    expect(method_exists(AiService::class, 'checkBudget'))->toBeTrue();
});

it('checkBudget returns true when budget is 0 (unlimited)', function () {
    $aiService = app(AiService::class);
    expect($aiService->checkBudget())->toBeTrue();
});

it('new settings are seedable', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    expect(\Modules\Settings\Models\Setting::where('key', 'ai.monthly_budget')->exists())->toBeTrue();
    expect(\Modules\Settings\Models\Setting::where('key', 'ai.auto_moderation_enabled')->exists())->toBeTrue();
    expect(\Modules\Settings\Models\Setting::where('key', 'ai.rag_enabled')->exists())->toBeTrue();
    expect(\Modules\Settings\Models\Setting::where('key', 'ai.chatbot_welcome_message')->exists())->toBeTrue();
});

it('analytics page renders with empty data', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.ai.analytics'))
        ->assertOk()
        ->assertSee('Aucune donn');
});
