<?php

declare(strict_types=1);

/**
 * Non-regression du correctif "date du jour dans le prompt IA" (AiSummaryService).
 *
 * Contexte du bug corrige : privé de tout repère temporel, le modèle comblait avec une année
 * de son entraînement (article publié le 2026-08-12 titré "... en 2024"). Le correctif injecte
 * `$today` (Carbon::now('America/Toronto')->locale('fr')->isoFormat('D MMMM YYYY')) dans les
 * deux prompts heredoc, avec une règle STRICTE interdisant d'inventer une année/date et une
 * consigne seo_title explicite "SANS année ni date".
 *
 * Le prompt part vers OpenRouter via la façade Http (callModelCascade(), POST
 * https://openrouter.ai/api/v1/chat/completions, body.messages[0].content = le prompt complet)
 * : il est donc observable via Http::fake() + Http::assertSent(), sans jamais lire le fichier
 * source (voir NewsFusionTest.php pour la même convention Pest + Http::fake).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\News\Services\AiSummaryService;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Fake OpenRouter : succès systématique sur le premier modèle, JSON minimal valide. */
function apdFakeOpenRouterSuccess(): void
{
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 8,
                'hook' => 'Accroche factuelle de test.',
            ])]]],
        ], 200),
    ]);
}

/** Attendu calculé avec le MÊME appel Carbon que le service - jamais une date codée en dur. */
function apdExpectedToday(): string
{
    return \Carbon\Carbon::now('America/Toronto')->locale('fr')->isoFormat('D MMMM YYYY');
}

it('scoreAndSummarize envoie un prompt contenant la date du jour et l interdiction d inventer une annee', function () {
    apdFakeOpenRouterSuccess();
    $today = apdExpectedToday();

    (new AiSummaryService())->scoreAndSummarize('Titre de test', 'Texte source suffisant pour le test.');

    Http::assertSent(function ($request) use ($today) {
        $content = $request->data()['messages'][0]['content'] ?? '';

        return str_contains($request->url(), 'openrouter.ai')
            && str_contains($content, "Date du jour : {$today}.")
            && str_contains($content, 'Ne JAMAIS inventer une année ni une date')
            && str_contains($content, 'SANS année ni date sauf si elle figure LITTÉRALEMENT dans le texte source');
    });
});

it('scoreAndSummarizeGroup envoie un prompt contenant la date du jour et l interdiction d inventer une annee', function () {
    apdFakeOpenRouterSuccess();
    $today = apdExpectedToday();

    $articles = [
        ['title' => 'Titre A', 'url' => 'https://exemple.com/a', 'author' => null, 'source_name' => 'SourceA', 'text' => 'Texte source A.'],
        ['title' => 'Titre B', 'url' => 'https://exemple.com/b', 'author' => null, 'source_name' => 'SourceB', 'text' => 'Texte source B.'],
    ];

    (new AiSummaryService())->scoreAndSummarizeGroup($articles, []);

    Http::assertSent(function ($request) use ($today) {
        $content = $request->data()['messages'][0]['content'] ?? '';

        return str_contains($request->url(), 'openrouter.ai')
            && str_contains($content, "Date du jour : {$today}.")
            && str_contains($content, 'Ne JAMAIS inventer une année ni une date')
            && str_contains($content, 'SANS année ni date sauf si elle figure LITTÉRALEMENT dans le texte source');
    });
});
