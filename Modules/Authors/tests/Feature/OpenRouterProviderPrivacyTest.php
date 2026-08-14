<?php

declare(strict_types=1);

/**
 * Non-régression conformité (extension 2026-08-13 de AiSummaryProviderPrivacyTest.php, module
 * News) : le texte source envoyé à un modèle ne doit JAMAIS être conservé par le sous-traitant
 * IA. Vérifie que chaque service du module Authors qui appelle OpenRouter porte bien
 * provider.data_collection = "deny" et provider.zdr = true, via le bloc partagé
 * Modules\Core\Services\OpenRouterPrivacy - jamais recopié. Couvre :
 * - ImagePipelineService::suggestAltText() (vision, image + texte) ;
 * - AnalyticsRecommendationService (méthode privée getAiRecommendations(), exercée via
 *   getCachedInsights()) ;
 * - ModerationPipelineService::scan() (2 appels OpenRouter par scan : Llama Guard + gpt-oss).
 *
 * Convention Pest + Http::fake identique à AiSummaryProviderPrivacyTest.php (jamais d'appel
 * réseau réel dans un test).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Authors\Services\AnalyticsRecommendationService;
use Modules\Authors\Services\ImagePipelineService;
use Modules\Authors\Services\ModerationPipelineService;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['services.openrouter.api_key' => 'test-key']);
});

function authorsProviderPrefsPresent(\Illuminate\Http\Client\Request $request): bool
{
    $body = $request->data();

    return str_contains($request->url(), 'openrouter.ai')
        && ($body['provider']['data_collection'] ?? null) === 'deny'
        && ($body['provider']['zdr'] ?? null) === true;
}

it('ImagePipelineService::suggestAltText() envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Une image de test.']]]], 200),
    ]);

    $imagePath = tempnam(sys_get_temp_dir(), 'authors_alt_text_test_').'.png';
    // PNG 1x1 minimal valide (évite une dépendance à un fichier fixture).
    file_put_contents($imagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

    try {
        (new ImagePipelineService())->suggestAltText($imagePath);
    } finally {
        @unlink($imagePath);
    }

    Http::assertSent(fn ($request) => authorsProviderPrefsPresent($request));
});

it('AnalyticsRecommendationService envoie provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '{"summary":"ok","recommendations":[],"best_publish_time":"","trending_topics":[]}']]]], 200),
    ]);

    $service = app(AnalyticsRecommendationService::class);
    $method = new ReflectionMethod($service, 'getAiRecommendations');
    $method->setAccessible(true);
    $method->invoke($service, ['clicks' => 10]);

    Http::assertSent(fn ($request) => authorsProviderPrefsPresent($request));
});

it('ModerationPipelineService::scan() envoie provider.data_collection=deny et provider.zdr=true sur chaque appel', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '{"severity":0}']]]], 200),
    ]);

    // ->create() (pas ->make()) : ModerationPipelineService::scan() persiste un ArticleModerationLog
    // avec article_id NOT NULL, donc l'article doit exister réellement en base (user_id NOT NULL
    // aussi - on laisse la factory créer son propre auteur plutôt que de le mettre à null).
    $article = Article::factory()->create(['content' => 'Contenu de test sans risque.']);

    app(ModerationPipelineService::class)->scan($article);

    Http::assertSent(fn ($request) => authorsProviderPrefsPresent($request));
    Http::assertSentCount(2); // Llama Guard + gpt-oss (runClaudeHaiku n'est appelé que si score > seuil).
});
