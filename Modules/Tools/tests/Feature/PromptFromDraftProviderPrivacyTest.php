<?php

declare(strict_types=1);

/*
 * Non-régression conformité (même convention que AiSummaryProviderPrivacyTest.php du module News et
 * OpenRouterProviderPrivacyTest.php du module AI) : le texte collé par l'utilisateur dans « Partir de
 * mon brouillon » (Brique 2, SPEC-BRIQUE2) passe par Modules\AI\Services\AiService::chat() TEL QUEL -
 * aucun nouveau client HTTP écrit dans Modules\Tools\Services\PromptFromDraftService. Ce test prouve
 * que le payload envoyé à OpenRouter porte bien provider.data_collection=deny et provider.zdr=true
 * (bloc partagé Modules\Core\Services\OpenRouterPrivacy::applyTo(), appliqué À L'INTÉRIEUR de
 * AiService::chat() - jamais recopié ici).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Settings\Models\Setting;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Setting::set('ai.openrouter_api_key', 'test-key', 'string', 'ai');
    Setting::set('ai.chatbot_model', 'meta-llama/llama-3.3-70b-instruct:free', 'string', 'ai');
    Setting::set('ai.temperature', '0.7', 'number', 'ai');
    Setting::set('ai.max_tokens', '2048', 'number', 'ai');
    Setting::set('ai.monthly_budget', '0', 'number', 'ai');
    config(['services.openrouter.api_key' => 'test-key']);
});

it('POST /outils/constructeur-prompts/depuis-brouillon envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'taskObject' => 'Rédige un courriel',
            'spaces' => [],
        ])]]]], 200),
    ]);

    $this->postJson('/outils/constructeur-prompts/depuis-brouillon', [
        'texte' => 'Un brouillon quelconque à transformer.',
    ])->assertOk();

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'openrouter.ai')
            && ($body['provider']['data_collection'] ?? null) === 'deny'
            && ($body['provider']['zdr'] ?? null) === true;
    });
});
