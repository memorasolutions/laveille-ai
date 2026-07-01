<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: tests de la cascade / auto-escalade de modèles dans AiService::chatWithHistory()
 * RAISON: zéro régression pour le Tuteur IA / Feedback IA / Authoring IA (tous appelants de
 *         chatWithHistory) — comportement par défaut (escalation_enabled=false) inchangé,
 *         escalade transparente (marqueur ESCALATE jamais exposé à l'appelant).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiService;
use Modules\Settings\Models\Setting;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Setting::set('ai.openrouter_api_key', 'test-key', 'string', 'ai');
    Setting::set('ai.chatbot_model', 'meta-llama/llama-3.3-70b-instruct:free', 'string', 'ai');
    Setting::set('ai.temperature', '0.7', 'number', 'ai');
    Setting::set('ai.max_tokens', '2048', 'number', 'ai');
});

it('escalation désactivée (défaut) : chatWithHistory se comporte exactement comme avant, un seul appel', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Réponse normale']]],
        ]),
    ]);

    $service = app(AiService::class);

    $result = $service->chatWithHistory([
        ['role' => 'system', 'content' => 'Tu es utile.'],
        ['role' => 'user', 'content' => 'Bonjour'],
    ]);

    expect($result)->toBe('Réponse normale');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->data()['model'] === 'meta-llama/llama-3.3-70b-instruct:free');
});

it('un marqueur ESCALATE: dans la réponse du modèle primaire déclenche un second appel au modèle d’escalade, transparent pour l’appelant', function () {
    Setting::set('ai.escalation_enabled', 'true', 'boolean', 'ai');
    Setting::set('ai.model_escalation', 'deepseek/deepseek-v3.2-20251201', 'string', 'ai');

    Http::fake([
        'openrouter.ai/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => 'ESCALATE: question trop ambiguë']]]], 200)
            ->push(['choices' => [['message' => ['content' => 'Réponse détaillée du modèle puissant']]]], 200),
    ]);

    $service = app(AiService::class);

    $result = $service->chatWithHistory([
        ['role' => 'system', 'content' => 'Tu es utile.'],
        ['role' => 'user', 'content' => 'Une question normale'],
    ]);

    // (d) le marqueur ESCALATE n'est JAMAIS visible dans la réponse finale.
    expect($result)->toBe('Réponse détaillée du modèle puissant')
        ->and($result)->not->toContain('ESCALATE');

    Http::assertSentCount(2);

    $requests = Http::recorded();
    expect($requests[0][0]->data()['model'])->toBe('meta-llama/llama-3.3-70b-instruct:free')
        ->and($requests[1][0]->data()['model'])->toBe('deepseek/deepseek-v3.2-20251201')
        // Le second appel repart des messages ORIGINAUX (pas d'instruction ESCALATE ajoutée).
        ->and($requests[1][0]->data()['messages'])->toHaveCount(2);
});

it('une règle heuristique (question très longue) déclenche l’escalade directe sans passer par le modèle primaire', function () {
    Setting::set('ai.escalation_enabled', 'true', 'boolean', 'ai');
    Setting::set('ai.model_escalation', 'deepseek/deepseek-v3.2-20251201', 'string', 'ai');
    Setting::set('ai.escalation_length_threshold', '2000', 'number', 'ai');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Réponse du modèle puissant']]],
        ]),
    ]);

    $service = app(AiService::class);
    $longQuestion = str_repeat('a', 2001);

    $result = $service->chatWithHistory([
        ['role' => 'system', 'content' => 'Tu es utile.'],
        ['role' => 'user', 'content' => $longQuestion],
    ]);

    expect($result)->toBe('Réponse du modèle puissant');

    // Un seul appel : direct au modèle d'escalade, jamais au modèle primaire.
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->data()['model'] === 'deepseek/deepseek-v3.2-20251201');
});

it('une règle heuristique par mot-clé déclenche aussi l’escalade directe', function () {
    Setting::set('ai.escalation_enabled', 'true', 'boolean', 'ai');
    Setting::set('ai.model_escalation', 'deepseek/deepseek-v3.2-20251201', 'string', 'ai');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Réponse du modèle puissant']]],
        ]),
    ]);

    $service = app(AiService::class);

    $result = $service->chatWithHistory([
        ['role' => 'user', 'content' => 'Peux-tu faire une analyse complète de ce document ?'],
    ]);

    expect($result)->toBe('Réponse du modèle puissant');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->data()['model'] === 'deepseek/deepseek-v3.2-20251201');
});

it('escalade activée mais aucun signal : comportement du modèle primaire retourné tel quel', function () {
    Setting::set('ai.escalation_enabled', 'true', 'boolean', 'ai');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Réponse simple, pas d’escalade']]],
        ]),
    ]);

    $service = app(AiService::class);

    $result = $service->chatWithHistory([
        ['role' => 'user', 'content' => 'Bonjour'],
    ]);

    expect($result)->toBe('Réponse simple, pas d’escalade');
    Http::assertSentCount(1);
});
