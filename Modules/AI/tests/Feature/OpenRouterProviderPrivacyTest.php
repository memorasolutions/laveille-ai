<?php

declare(strict_types=1);

/**
 * Non-régression conformité (extension 2026-08-13 de AiSummaryProviderPrivacyTest.php, module
 * News) : le texte source envoyé à un modèle ne doit JAMAIS être conservé par le sous-traitant
 * IA. Vérifie que chaque service du module AI qui appelle OpenRouter porte bien
 * provider.data_collection = "deny" et provider.zdr = true (bloc partagé
 * Modules\Core\Services\OpenRouterPrivacy, jamais recopié). Couvre :
 * - AiService::chat() et AiService::chatWithHistory() (deux chemins HTTP distincts avant
 *   refactor éventuel, tous deux passent par le bloc partagé) ;
 * - YouTubeService::summarize() et YouTubeService::summarizeFromMeta() ;
 * - EmbeddingService::embed() et EmbeddingService::embedBatch().
 *
 * Convention Pest + Http::fake identique à AiSummaryProviderPrivacyTest.php (jamais d'appel
 * réseau réel dans un test).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiService;
use Modules\AI\Services\EmbeddingService;
use Modules\AI\Services\YouTubeService;
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

function providerPrefsPresent(\Illuminate\Http\Client\Request $request): bool
{
    $body = $request->data();

    return str_contains($request->url(), 'openrouter.ai')
        && ($body['provider']['data_collection'] ?? null) === 'deny'
        && ($body['provider']['zdr'] ?? null) === true;
}

it('AiService::chat() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Réponse test']]]], 200),
    ]);

    app(AiService::class)->chat('Bonjour');

    Http::assertSent(fn ($request) => providerPrefsPresent($request));
});

it('AiService::chatWithHistory() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Réponse test']]]], 200),
    ]);

    app(AiService::class)->chatWithHistory([
        ['role' => 'user', 'content' => 'Bonjour'],
    ]);

    Http::assertSent(fn ($request) => providerPrefsPresent($request));
});

it('YouTubeService::summarize() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Résumé test']]]], 200),
    ]);

    (new YouTubeService())->summarize('Transcription de test unique '.uniqid());

    Http::assertSent(fn ($request) => providerPrefsPresent($request));
});

it('YouTubeService::summarizeFromMeta() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Résumé meta test']]]], 200),
    ]);

    (new YouTubeService())->summarizeFromMeta(
        'Titre vidéo '.uniqid(),
        'Chaîne test',
        'Outil test',
        'Description outil test'
    );

    Http::assertSent(fn ($request) => providerPrefsPresent($request));
});

it('EmbeddingService::embed() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3]]]], 200),
    ]);

    app(EmbeddingService::class)->embed('Texte à embedder');

    Http::assertSent(fn ($request) => providerPrefsPresent($request));
});

it('EmbeddingService::embedBatch() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['data' => [['embedding' => [0.1]], ['embedding' => [0.2]]]], 200),
    ]);

    app(EmbeddingService::class)->embedBatch(['Texte 1', 'Texte 2']);

    Http::assertSent(fn ($request) => providerPrefsPresent($request));
});
