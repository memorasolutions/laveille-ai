<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: tests de la commande ai:bench (SPEC-BANC-ESSAI-IA) - AUCUN appel réseau réel,
 * Http::fake() systématique. Couvre : un fichier de sortie cohérent est produit, un cas de
 * succès passe, un JSON invalide échoue, une exception réseau devient une ligne ECHEC
 * isolée (jamais un crash de la commande), et le payload envoyé porte bien
 * provider.data_collection=deny + provider.zdr=true (même garde que les autres services
 * OpenRouter du module).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\AiBenchAssertionService;
use Modules\AI\Services\AiBenchService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['services.openrouter.api_key' => 'test-key']);
});

function benchService(): AiBenchService
{
    return new AiBenchService(new AiBenchAssertionService());
}

it('trouve les tâches gelées livrées avec le module', function () {
    $tasks = benchService()->availableTasks();

    expect($tasks)->toContain('extraction', 'summary', 'translation');
});

it('charge au moins trois cas gelés par tâche de démarrage', function () {
    $bench = benchService();

    foreach (['extraction', 'summary', 'translation'] as $task) {
        $cases = $bench->loadCases($task);
        expect(count($cases))->toBeGreaterThanOrEqual(3)
            ->and($cases[0])->toHaveKeys(['id', 'input', 'assertions']);
    }
});

it('un cas de succès passe et récupère latence, tokens et coût rapporté', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"taskObject":"Envoyer un courriel","spaces":[{"text":"un courriel"}]}']]],
            'usage' => ['total_tokens' => 120, 'cost' => 0.00042],
        ], 200),
    ]);

    $bench = benchService();
    $case = $bench->loadCases('extraction')[0];

    $row = $bench->runCase('extraction', $case, 'openai/gpt-4o-mini');

    expect($row['status'])->toBe('PASS')
        ->and($row['tokens'])->toBe(120)
        ->and($row['cost'])->toBe(0.00042)
        ->and($row['cost_source'])->toBe('rapporte')
        ->and($row['latency_ms'])->toBeFloat();
});

it('un JSON invalide échoue l\'assertion json_valid sans planter la commande', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'ceci nest pas du JSON']]],
            'usage' => ['total_tokens' => 50],
        ], 200),
    ]);

    $bench = benchService();
    $case = $bench->loadCases('extraction')[0];

    $row = $bench->runCase('extraction', $case, 'openai/gpt-4o-mini');

    expect($row['status'])->toBe('FAIL')
        ->and($row['reason'])->toContain('JSON invalide');
});

it('un coût non rapporté par OpenRouter tombe sur une estimation marquée comme telle', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"taskObject":"x","spaces":[]}']]],
            'usage' => ['total_tokens' => 200, 'prompt_tokens' => 150, 'completion_tokens' => 50],
        ], 200),
    ]);

    $bench = benchService();
    $case = $bench->loadCases('extraction')[0];

    $row = $bench->runCase('extraction', $case, 'openai/gpt-4o-mini');

    expect($row['cost_source'])->toBe('estime')
        ->and($row['cost'])->toBeGreaterThan(0.0);
});

it('une exception réseau (timeout) produit une ligne ECHEC isolée, jamais un crash', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Timeout simulé');
    });

    $bench = benchService();
    $case = $bench->loadCases('summary')[0];

    $row = $bench->runCase('summary', $case, 'openai/gpt-4o-mini');

    expect($row['status'])->toBe('ECHEC')
        ->and($row['reason'])->toContain('exception réseau');
});

it('une réponse HTTP en erreur devient une ligne ECHEC avec le message renvoyé', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => ['message' => 'rate limited']], 429),
    ]);

    $bench = benchService();
    $case = $bench->loadCases('translation')[0];

    $row = $bench->runCase('translation', $case, 'deepseek/deepseek-chat');

    expect($row['status'])->toBe('ECHEC')
        ->and($row['reason'])->toContain('rate limited');
});

it('le payload envoyé porte provider.data_collection=deny et provider.zdr=true', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"taskObject":"x","spaces":[]}']]],
            'usage' => ['total_tokens' => 80, 'cost' => 0.0001],
        ], 200),
    ]);

    $bench = benchService();
    $case = $bench->loadCases('extraction')[0];
    $bench->runCase('extraction', $case, 'openai/gpt-4o-mini');

    Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
        $body = $request->data();

        return str_contains($request->url(), 'openrouter.ai')
            && ($body['provider']['data_collection'] ?? null) === 'deny'
            && ($body['provider']['zdr'] ?? null) === true
            && ($body['usage']['include'] ?? null) === true;
    });
});

it('la commande ai:bench produit un fichier Markdown cohérent avec un tableau par tâche', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"taskObject":"Envoyer un courriel","spaces":[{"text":"un courriel"}]}']]],
            'usage' => ['total_tokens' => 100, 'cost' => 0.0002],
        ], 200),
    ]);

    $outPath = storage_path('app/ai-bench-test-'.uniqid().'.md');

    $this->artisan('ai:bench', [
        '--task' => 'extraction',
        '--models' => 'openai/gpt-4o-mini',
        '--out' => $outPath,
    ])->assertExitCode(0);

    expect(file_exists($outPath))->toBeTrue();

    $content = file_get_contents($outPath);
    expect($content)->toContain('## Tâche : extraction')
        ->and($content)->toContain('openai/gpt-4o-mini')
        ->and($content)->toContain('Réussite');

    @unlink($outPath);
});

it('ai:bench sur une tâche inconnue échoue proprement sans planter', function () {
    $this->artisan('ai:bench', ['--task' => 'tache-inexistante-xyz'])
        ->assertExitCode(1);
});

it('les assertions du registre couvrent succès et échec pour chaque type', function () {
    $assertions = new AiBenchAssertionService();

    expect($assertions->evaluate(['type' => 'json_valid'], '{"a":1}')['ok'])->toBeTrue()
        ->and($assertions->evaluate(['type' => 'json_valid'], 'pas du json')['ok'])->toBeFalse()
        ->and($assertions->evaluate(['type' => 'json_has_keys', 'keys' => ['a']], '{"a":1}')['ok'])->toBeTrue()
        ->and($assertions->evaluate(['type' => 'json_has_keys', 'keys' => ['b']], '{"a":1}')['ok'])->toBeFalse()
        ->and($assertions->evaluate(['type' => 'length_between', 'min' => 1, 'max' => 5], 'abc')['ok'])->toBeTrue()
        ->and($assertions->evaluate(['type' => 'length_between', 'min' => 10, 'max' => 20], 'abc')['ok'])->toBeFalse()
        ->and($assertions->evaluate(['type' => 'lang_fr'], 'Ceci est une phrase française avec des accents comme é et è.')['ok'])->toBeTrue()
        ->and($assertions->evaluate(['type' => 'lang_fr'], 'This is plain English text.')['ok'])->toBeFalse()
        ->and($assertions->evaluate(['type' => 'lang_en'], 'This is the plain English text that is written for the test.')['ok'])->toBeTrue()
        ->and($assertions->evaluate(['type' => 'no_refusal'], 'Voici la réponse demandée.')['ok'])->toBeTrue()
        ->and($assertions->evaluate(['type' => 'no_refusal'], "Je suis désolé, je ne peux pas répondre.")['ok'])->toBeFalse();
});
