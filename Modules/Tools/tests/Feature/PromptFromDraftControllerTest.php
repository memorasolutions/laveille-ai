<?php

declare(strict_types=1);

/*
 * Brique 2 - « Partir de mon brouillon » (SPEC-BRIQUE2). Endpoint POST
 * /outils/constructeur-prompts/depuis-brouillon (PromptFromDraftController::transform() /
 * PromptFromDraftService::transform()). L'appel LLM est TOUJOURS mocké via Http::fake() - jamais
 * d'appel réseau réel dans un test (même convention que AiSummaryProviderPrivacyTest.php).
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

it('un texte valide renvoie un params avec taskObject et des spaces ancrables', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'taskObject' => 'Rédige un courriel de suivi pour Jean Tremblay au sujet du projet Alpha',
            'spaces' => [
                ['text' => 'Jean Tremblay'],
                ['text' => 'projet Alpha'],
                // Non-ancrable : n'apparaît pas dans taskObject - doit être filtré.
                ['text' => 'ce texte absent'],
            ],
            'contextInfo' => 'Le client a déjà reçu une première version',
            'verb' => 'Rédige',
            'tone' => 'professionnel',
        ])]]]], 200),
    ]);

    $response = $this->postJson('/outils/constructeur-prompts/depuis-brouillon', [
        'texte' => "Bonjour, je dois écrire un courriel de suivi pour Jean Tremblay sur le projet Alpha.",
    ]);

    $response->assertOk();
    $params = $response->json('params');

    expect($params['taskObject'])->toBe('Rédige un courriel de suivi pour Jean Tremblay au sujet du projet Alpha')
        ->and($params['spaces'])->toBe([['text' => 'Jean Tremblay'], ['text' => 'projet Alpha']])
        ->and($params['contextInfo'])->toBe('Le client a déjà reçu une première version')
        ->and($params['verb'])->toBe('Rédige')
        ->and($params['tone'])->toBe('professionnel')
        // Aucune clé étrangère au format `params` du wizard ne doit fuiter.
        ->and($params)->not->toHaveKey('unauthorized_key');
});

it('un texte vide renvoie 422', function () {
    $response = $this->postJson('/outils/constructeur-prompts/depuis-brouillon', ['texte' => '']);

    $response->assertStatus(422);
});

it('le champ texte manquant renvoie 422', function () {
    $response = $this->postJson('/outils/constructeur-prompts/depuis-brouillon', []);

    $response->assertStatus(422);
});

it('un JSON invalide renvoyé par le modèle renvoie 422 propre, jamais un 500', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'ceci n\'est pas du JSON']]]], 200),
    ]);

    $response = $this->postJson('/outils/constructeur-prompts/depuis-brouillon', [
        'texte' => 'Un texte quelconque à transformer.',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['message']);
});

it('un taskObject vide dans la réponse du modèle renvoie 422, jamais un cœur vide accepté', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'taskObject' => '',
            'spaces' => [],
        ])]]]], 200),
    ]);

    $response = $this->postJson('/outils/constructeur-prompts/depuis-brouillon', [
        'texte' => 'Un texte quelconque à transformer.',
    ]);

    $response->assertStatus(422);
});

it('les espaces non-sous-chaînes de taskObject sont filtrés', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'taskObject' => 'Planifie une réunion avec Marie',
            'spaces' => [
                ['text' => 'Marie'],
                ['text' => 'texte inventé non présent'],
                ['text' => ''],
            ],
        ])]]]], 200),
    ]);

    $response = $this->postJson('/outils/constructeur-prompts/depuis-brouillon', [
        'texte' => 'Je dois planifier une réunion avec Marie.',
    ]);

    $response->assertOk();
    expect($response->json('params.spaces'))->toBe([['text' => 'Marie']]);
});

it('le texte de plus de 4000 caractères est tronqué avant l\'appel au modèle', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'taskObject' => 'Résume ce long texte',
            'spaces' => [],
        ])]]]], 200),
    ]);

    $longText = str_repeat('a', 4500).'MARQUEUR_FIN_JAMAIS_ENVOYE';

    $response = $this->postJson('/outils/constructeur-prompts/depuis-brouillon', [
        'texte' => $longText,
    ]);

    $response->assertOk();

    Http::assertSent(function ($request) {
        $body = $request->data();
        $sentContent = $body['messages'][array_key_last($body['messages'])]['content'] ?? '';

        return ! str_contains($sentContent, 'MARQUEUR_FIN_JAMAIS_ENVOYE');
    });
});

it('le throttle limite à 5 requêtes par 60 secondes', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'taskObject' => 'Une tâche',
            'spaces' => [],
        ])]]]], 200),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/outils/constructeur-prompts/depuis-brouillon', ['texte' => 'Texte de test '.$i])
            ->assertOk();
    }

    $this->postJson('/outils/constructeur-prompts/depuis-brouillon', ['texte' => 'Texte de trop'])
        ->assertStatus(429);
});
