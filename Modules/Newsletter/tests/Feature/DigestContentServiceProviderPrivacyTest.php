<?php

declare(strict_types=1);

/**
 * Non-régression conformité (extension 2026-08-13 de AiSummaryProviderPrivacyTest.php, module
 * News) : le texte source envoyé à un modèle ne doit JAMAIS être conservé par le sous-traitant
 * IA. Vérifie que DigestContentService (mini-éditorial + prompt hebdo dynamique) porte bien
 * provider.data_collection = "deny" et provider.zdr = true sur chacun de ses deux appels
 * OpenRouter, via le bloc partagé Modules\Core\Services\OpenRouterPrivacy - jamais recopié.
 *
 * Convention Pest + Http::fake identique à AiSummaryProviderPrivacyTest.php (jamais d'appel
 * réseau réel dans un test).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Newsletter\Services\DigestContentService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['services.openrouter.api_key' => 'test-key']);
});

it('generateEditorial() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Édito de test - Stephane']]]], 200),
    ]);

    DigestContentService::generateEditorial('Titre de test', 'Résumé de test');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'openrouter.ai')
            && ($body['provider']['data_collection'] ?? null) === 'deny'
            && ($body['provider']['zdr'] ?? null) === true;
    });
});

it('generateDynamicPrompt() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Prompt de test.']]]], 200),
    ]);

    DigestContentService::generateDynamicPrompt('terme de test', 1);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'openrouter.ai')
            && ($body['provider']['data_collection'] ?? null) === 'deny'
            && ($body['provider']['zdr'] ?? null) === true;
    });
});
