<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif incident « qwen3-max vs rétention nulle » (2026-08-23) : OpenRouterService::generate()
 * et classifyPricing() appelaient qwen/qwen3-max en dur, un modèle sans AUCUN fournisseur conforme
 * à la politique de confidentialité imposée par OpenRouterPrivacy::applyTo() (rétention nulle) -
 * chaque appel se soldait par un HTTP 404 "No endpoints found matching your data policy (Zero data
 * retention)", journalisé sur le canal PAR DÉFAUT et donc avalé par LOG_LEVEL=error en production.
 * Cette suite prouve trois garanties du correctif :
 *   1. Un refus de politique de données sur le 1er modèle de la cascade fait passer immédiatement
 *      au 2e (sans réessai gaspillé sur le 1er), et le résultat du 2e est bien celui retourné.
 *   2. Si TOUS les modèles de la cascade refusent, generate() renvoie '' et journalise l'échec.
 *   3. Cet avertissement survit à LOG_LEVEL=error en production - une preuve par fichier de log
 *      réel, pas par mock (un mock ne prouve que l'appel, jamais la survie au filtrage de niveau).
 *      Même méthode que Modules/Directory/tests/Feature/ToolDiscoveryUrlResolutionTest.php
 *      (canal 'directory_discovery') et docs/CONTRAINTES-SOUS-AGENTS.md section 6.
 */

use Illuminate\Support\Facades\Http;
use Modules\Directory\Services\OpenRouterService;

uses(Tests\TestCase::class);

/** Chemin du fichier daily du jour pour le canal 'directory_enrichment' (voir config/logging.php). */
function orscDedicatedLogPath(): string
{
    return storage_path('logs/directory_enrichment-'.now()->format('Y-m-d').'.log');
}

/** Chemin du fichier daily du jour pour le canal PAR DÉFAUT du projet (.env : LOG_CHANNEL=daily). */
function orscDefaultLogPath(): string
{
    return storage_path('logs/laravel-'.now()->format('Y-m-d').'.log');
}

/** Repart de fichiers de log vides pour isoler le contenu produit par CE test. */
function orscResetLogs(): void
{
    @unlink(orscDedicatedLogPath());
    @unlink(orscDefaultLogPath());
}

/** Corps de réponse OpenRouter d'un refus de politique de données (rétention nulle). */
function orscDataPolicyRefusalBody(): array
{
    return ['error' => ['message' => 'No endpoints found matching your data policy (Zero data retention)']];
}

beforeEach(function () {
    config(['directory.openrouter_api_key' => 'test-key']);
});

it('un refus de politique de données sur le 1er modèle passe immédiatement au 2e, sans réessai, et le résultat du 2e est utilisé', function () {
    config(['directory.openrouter_writer_models' => ['modele-a/refuse-donnees', 'modele-b/conforme']]);

    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        $model = $request->data()['model'] ?? null;

        if ($model === 'modele-a/refuse-donnees') {
            return Http::response(orscDataPolicyRefusalBody(), 404);
        }

        if ($model === 'modele-b/conforme') {
            return Http::response(['choices' => [['message' => ['content' => 'Réponse du second modèle']]]], 200);
        }

        return Http::response('modèle inattendu : '.$model, 500);
    });

    $result = (new OpenRouterService())->generate('Rédige une fiche de test.');

    expect($result)->toBe('Réponse du second modèle');

    // Exactement 2 requêtes : le modèle refusé n'a PAS été réessayé (un refus de politique de
    // données est définitif) avant de passer au suivant, qui a réussi du premier coup.
    Http::assertSentCount(2);
});

it('si TOUS les modèles de la cascade refusent, generate() renvoie une chaîne vide', function () {
    config(['directory.openrouter_writer_models' => ['modele-a/refuse-donnees', 'modele-b/refuse-donnees']]);

    Http::fake(fn () => Http::response(orscDataPolicyRefusalBody(), 404));

    $result = (new OpenRouterService())->generate('Rédige une fiche de test.');

    expect($result)->toBe('');
    // Un modèle refusé n'est jamais réessayé -> exactement 1 requête par modèle de la cascade.
    Http::assertSentCount(2);
});

it('l\'échec total de la cascade s\'écrit sur le canal directory_enrichment, pas sur le canal par défaut, même avec un niveau de log global très restrictif', function () {
    // Simule la config de PRODUCTION diagnostiquée (LOG_LEVEL=error) - ici encore plus restrictif
    // ('emergency') - pour prouver que SEUL le hard-code 'level' => 'info' du canal
    // 'directory_enrichment' (config/logging.php) rend la ligne observable, indépendamment de
    // tout réglage global.
    config([
        'directory.openrouter_writer_models' => ['modele-a/refuse-donnees', 'modele-b/refuse-donnees'],
        'logging.channels.daily.level' => 'emergency',
        'logging.channels.single.level' => 'emergency',
    ]);

    orscResetLogs();

    Http::fake(fn () => Http::response(orscDataPolicyRefusalBody(), 404));

    $result = (new OpenRouterService())->generate('Rédige une fiche de test.');

    expect($result)->toBe('');

    // Canal dédié : le fichier existe et porte la ligne attendue - modèle, code HTTP et motif réel.
    expect(file_exists(orscDedicatedLogPath()))->toBeTrue();
    $dedicated = file_get_contents(orscDedicatedLogPath());
    expect($dedicated)->toContain('modele-a/refuse-donnees')
        ->and($dedicated)->toContain('404')
        ->and($dedicated)->toContain('data policy')
        ->and($dedicated)->toContain('cascade épuisée');

    // Canal par défaut : rien n'y a fuité (fichier absent, ou présent mais sans cette ligne).
    if (file_exists(orscDefaultLogPath())) {
        expect(file_get_contents(orscDefaultLogPath()))->not->toContain('data policy');
    }
});
