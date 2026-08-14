<?php

declare(strict_types=1);

/**
 * Non-régression conformité (extension 2026-08-13 de AiSummaryProviderPrivacyTest.php, module
 * News) : le texte source envoyé à un modèle ne doit JAMAIS être conservé par le sous-traitant
 * IA. Vérifie que TranslationService::translate() porte bien provider.data_collection = "deny"
 * et provider.zdr = true, via le bloc partagé Modules\Core\Services\OpenRouterPrivacy - jamais
 * recopié.
 *
 * Convention Pest + Http::fake identique à AiSummaryProviderPrivacyTest.php (jamais d'appel
 * réseau réel dans un test).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Services\TranslationService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('TranslationService::translate() envoie provider.data_collection=deny et provider.zdr=true', function () {
    config(['services.openrouter.api_key' => 'test-key']);

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Translated text output.']]]], 200),
    ]);

    // Texte clairement anglais (looksLikeFrench() doit retourner false) et unique par run
    // (le cache 24h est basé sur md5(texte+langues), un texte figé ferait passer le test au
    // premier lancement seulement).
    TranslationService::translate('This is a unique english test sentence '.uniqid(), 'en', 'fr');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'openrouter.ai')
            && ($body['provider']['data_collection'] ?? null) === 'deny'
            && ($body['provider']['zdr'] ?? null) === true;
    });
});
