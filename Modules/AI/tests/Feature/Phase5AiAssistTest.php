<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AI\Models\Ticket;
use Modules\AI\Services\AiService;
use Modules\AI\Services\SentimentService;
use Modules\AI\Services\SmartReplyService;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// --- Smart replies ---

it('guest ne peut pas accéder au suggest endpoint', function () {
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);
    $this->postJson(route('admin.ai.ai-assist.suggest', $ticket))
        ->assertUnauthorized();
});

it('user reçoit 403 sur suggest endpoint', function () {
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);
    $this->actingAs($this->user)
        ->postJson(route('admin.ai.ai-assist.suggest', $ticket))
        ->assertForbidden();
});

it('admin peut obtenir des suggestions de réponse', function () {
    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('chat')->andReturn('["Réponse 1","Réponse 2","Réponse 3"]');
        $mock->shouldReceive('checkBudget')->andReturn(true);
    });
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.ai.ai-assist.suggest', $ticket))
        ->assertOk()
        ->assertJsonStructure(['suggestions']);
});

it('SmartReplyService retourne un tableau vide pour JSON invalide', function () {
    $mockAi = Mockery::mock(AiService::class);
    $mockAi->shouldReceive('chat')->andReturn('invalid response');

    $service = new SmartReplyService($mockAi);
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

    $result = $service->suggestReplies($ticket);
    expect($result)->toBeArray()->toBeEmpty();
});

// --- Sentiment ---

it('guest ne peut pas accéder au sentiment endpoint', function () {
    $this->postJson(route('admin.ai.ai-assist.sentiment'), ['text' => 'test'])
        ->assertUnauthorized();
});

it('admin peut analyser le sentiment', function () {
    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('chat')->andReturn('{"sentiment":"positive","confidence":0.9,"summary":"Client satisfait"}');
    });

    $this->actingAs($this->admin)
        ->postJson(route('admin.ai.ai-assist.sentiment'), ['text' => 'Merci beaucoup !'])
        ->assertOk()
        ->assertJson(['sentiment' => 'positive']);
});

it('SentimentService retourne neutral en fallback', function () {
    $mockAi = Mockery::mock(AiService::class);
    $mockAi->shouldReceive('chat')->andReturn('garbage');

    $service = new SentimentService($mockAi);
    $result = $service->analyze('test');

    expect($result['sentiment'])->toBe('neutral')
        ->and($result['confidence'])->toBe(0.0);
});

it('sentiment endpoint valide text requis', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.ai.ai-assist.sentiment'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['text']);
});

// --- Rewrite ---

it('guest ne peut pas accéder au rewrite endpoint', function () {
    $this->postJson(route('admin.ai.ai-assist.rewrite'), ['content' => 'test', 'style' => 'professional'])
        ->assertUnauthorized();
});

it('admin peut réécrire une réponse', function () {
    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('rewriteContent')->andReturn('Texte amélioré');
    });

    $this->actingAs($this->admin)
        ->postJson(route('admin.ai.ai-assist.rewrite'), [
            'content' => 'Réponse brouillon',
            'style' => 'professional',
        ])
        ->assertOk()
        ->assertJson(['content' => 'Texte amélioré']);
});

it('rewrite valide le style enum', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.ai.ai-assist.rewrite'), [
            'content' => 'test',
            'style' => 'invalid',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['style']);
});
