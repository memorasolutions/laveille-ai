<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Modules\AI\Livewire\AiContentAssistant;
use Modules\AI\Livewire\AiSeoAssistant;
use Modules\AI\Services\AiService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('AiService has rewriteContent method', function () {
    expect(method_exists(AiService::class, 'rewriteContent'))->toBeTrue();
});

it('AiService has improveContent method', function () {
    expect(method_exists(AiService::class, 'improveContent'))->toBeTrue();
});

it('rewriteContent returns original on empty input', function () {
    $aiService = app(AiService::class);
    expect($aiService->rewriteContent(''))->toBe('');
});

it('improveContent returns original on empty input', function () {
    $aiService = app(AiService::class);
    expect($aiService->improveContent(''))->toBe('');
});

it('AiContentAssistant component renders', function () {
    Livewire::test(AiContentAssistant::class)->assertStatus(200);
});

it('AiContentAssistant has process method', function () {
    expect(method_exists(AiContentAssistant::class, 'process'))->toBeTrue();
});

it('AiContentAssistant has applyResult method', function () {
    expect(method_exists(AiContentAssistant::class, 'applyResult'))->toBeTrue();
});

it('AiSeoAssistant component renders', function () {
    Livewire::test(AiSeoAssistant::class)->assertStatus(200);
});

it('AiSeoAssistant has generate method', function () {
    expect(method_exists(AiSeoAssistant::class, 'generate'))->toBeTrue();
});

it('moderation batch requires authentication', function () {
    $this->post(route('admin.ai.moderation.batch'))->assertRedirect();
});

it('moderation batch validates input', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->post(route('admin.ai.moderation.batch'), [])
        ->assertSessionHasErrors(['comment_ids', 'action']);
});

it('Livewire components registered in service provider', function () {
    expect(Livewire::new('ai-content-assistant'))->toBeInstanceOf(AiContentAssistant::class);
    expect(Livewire::new('ai-seo-assistant'))->toBeInstanceOf(AiSeoAssistant::class);
});
