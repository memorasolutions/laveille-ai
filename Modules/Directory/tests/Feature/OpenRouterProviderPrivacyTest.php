<?php

declare(strict_types=1);

/**
 * Non-régression conformité (extension 2026-08-13 de AiSummaryProviderPrivacyTest.php, module
 * News) : le texte source envoyé à un modèle ne doit JAMAIS être conservé par le sous-traitant
 * IA. Vérifie que chaque appelant OpenRouter du module Directory porte bien
 * provider.data_collection = "deny" et provider.zdr = true, via le bloc partagé
 * Modules\Core\Services\OpenRouterPrivacy - jamais recopié. Couvre :
 * - OpenRouterService::generate() (utilisé par classifyPricing()/generate()/summarize()) ;
 * - EnrichTutorialsSonarCommand (commande artisan tools:enrich-tutorials-sonar, recherche
 *   Perplexity Sonar via OpenRouter).
 *
 * Convention Pest + Http::fake identique à AiSummaryProviderPrivacyTest.php (jamais d'appel
 * réseau réel dans un test).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function directoryProviderPrefsPresent(\Illuminate\Http\Client\Request $request): bool
{
    $body = $request->data();

    return str_contains($request->url(), 'openrouter.ai')
        && ($body['provider']['data_collection'] ?? null) === 'deny'
        && ($body['provider']['zdr'] ?? null) === true;
}

it('OpenRouterService::generate() envoie provider.data_collection=deny et provider.zdr=true', function () {
    config(['directory.openrouter_api_key' => 'test-key']);

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Réponse test']]]], 200),
    ]);

    (new OpenRouterService())->generate('Prompt de test');

    Http::assertSent(fn ($request) => directoryProviderPrefsPresent($request));
});

it('EnrichTutorialsSonarCommand envoie provider.data_collection=deny et provider.zdr=true à chaque recherche Sonar', function () {
    config(['services.openrouter.api_key' => 'test-key']);

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '[]']]]], 200),
    ]);

    // Construction directe (pas de ToolFactory dans ce module) - même convention que
    // Modules/Directory/tests/Feature/PublicFocalCropperTest.php:makePublicFocalTestTool().
    config(['app.locale' => 'fr_CA']);
    $slug = 'outil-sonar-test-'.uniqid();
    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Sonar Test');
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-sonar-test.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->tutorials_last_scanned_at = null;
    $tool->save();

    // --slug cible l'outil directement (chemin qui n'utilise pas withCount()->having(), lequel
    // échoue sur SQLite : "HAVING clause on a non-aggregate query" - limitation pré-existante de
    // l'environnement de test, indépendante de ce correctif de confidentialité).
    $this->artisan('tools:enrich-tutorials-sonar', ['--slug' => $slug, '--force' => true])
        ->assertExitCode(0);

    Http::assertSent(fn ($request) => directoryProviderPrefsPresent($request));
});
