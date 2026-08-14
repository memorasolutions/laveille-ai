<?php

declare(strict_types=1);

/**
 * Non-régression conformité : le texte source d'un article ne doit JAMAIS être conservé par le
 * sous-traitant IA (règle non négociable du propriétaire). Deux garde-fous vérifiés ici :
 *
 * 1. Chaque requête sortante vers OpenRouter (callModelCascade(), POST
 *    https://openrouter.ai/api/v1/chat/completions) porte les préférences de fournisseur
 *    provider.data_collection = "deny" et provider.zdr = true, lues depuis
 *    config('services.openrouter.data_collection'|'zdr') - jamais codées en dur.
 * 2. La cascade de modèles (config('services.openrouter.summary_models'), source de vérité
 *    UNIQUE, plus de duplication dans AiSummaryService) est utilisée dans l'ORDRE déclaré :
 *    openai/gpt-4o-mini en premier (politique de rétention la plus protectrice parmi les
 *    modèles déjà utilisés), deepseek/deepseek-chat en dernier recours seulement (sa politique
 *    publiée admet rétention sans durée bornée et entraînement sur les données reçues).
 *
 * Convention Pest + Http::fake identique à AiSummaryPromptDateTest.php (jamais d'appel réseau
 * réel dans un test).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\News\Services\AiSummaryService;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * JSON complet valide pour une réponse OpenRouter réussie. Couvre TOUS les champs requis
 * depuis le recalibrage 2026-08-13 de la porte de qualité (Modules\News\config\config.php,
 * news.quality_gate.required_fields) - un JSON minimal serait rejeté et masquerait le
 * comportement réellement testé ici (ordre de la cascade de modèles).
 */
function appFakeSuccessBody(): array
{
    return [
        'choices' => [['message' => ['content' => json_encode([
            'score' => 8,
            'score_justification' => 'Pertinent pour le test.',
            'category' => 'IA générative',
            'impact' => 'Moyen',
            'tldr' => 'Une entreprise technologique lance un nouvel outil francophone pour les équipes de développement.',
            'hook' => 'Accroche factuelle de test.',
            'key_points' => ['Premier fait détaillé du test.', 'Deuxième fait détaillé du test.'],
            'why_important' => 'Ce changement modifie concrètement le travail quotidien des professionnels visés par le test.',
            'audience' => ['développeurs', 'entreprises'],
            'seo_title' => 'Titre de test',
            'meta_description' => 'Description meta de test suffisamment courte pour la borne configurée par défaut.',
            'faq_question' => 'Pourquoi cet outil intéresse-t-il les équipes francophones ?',
            'faq_answer' => 'Parce qu\'il répond à un besoin concret de localisation resté sans solution jusqu\'ici.',
        ])]]],
    ];
}

it('envoie provider.data_collection=deny et provider.zdr=true dans chaque requête OpenRouter', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(appFakeSuccessBody(), 200),
    ]);

    (new AiSummaryService())->scoreAndSummarize('Titre de test', 'Texte source suffisant pour le test.');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), 'openrouter.ai')
            && ($body['provider']['data_collection'] ?? null) === 'deny'
            && ($body['provider']['zdr'] ?? null) === true;
    });
});

it('respecte config(services.openrouter.summary_models) comme unique source de la cascade, sans le modèle retiré', function () {
    $cascade = config('services.openrouter.summary_models');

    expect($cascade)->toBe(['openai/gpt-4o-mini', 'deepseek/deepseek-chat'])
        ->and($cascade)->not->toContain('google/gemma-3-27b-it:free');
});

it('essaie openai/gpt-4o-mini avant deepseek/deepseek-chat quand le premier modèle échoue', function () {
    Http::fake([
        'openrouter.ai/*' => Http::sequence()
            ->push(['error' => ['message' => 'échec simulé du premier modèle']], 500)
            ->push(appFakeSuccessBody(), 200),
    ]);

    $result = (new AiSummaryService())->scoreAndSummarize('Titre de test', 'Texte source suffisant pour le test.');

    expect($result)->not->toBeNull();

    Http::assertSentInOrder([
        fn ($request) => str_contains($request->url(), 'openrouter.ai')
            && ($request->data()['model'] ?? null) === 'openai/gpt-4o-mini',
        fn ($request) => str_contains($request->url(), 'openrouter.ai')
            && ($request->data()['model'] ?? null) === 'deepseek/deepseek-chat',
    ]);
});
