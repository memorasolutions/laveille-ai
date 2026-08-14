<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif incident SceneNote (2026-08-14) : tools:reenrich-stale a écrit qu'aucun site
 * officiel n'existait pour SceneNote alors que l'adresse figurait déjà dans la fiche au moment
 * de la régénération. Cette suite prouve trois garanties :
 *   1. La commande ne s'exécute pas quand DIRECTORY_REENRICH_STALE_ENABLED est absent (défaut
 *      false) - aucun appel réseau, fiche existante intacte.
 *   2. Une fiche n'est jamais réécrite quand la recherche ne donne rien.
 *   3. Une sortie contenant une affirmation d'absence est rejetée par la porte de qualité et
 *      n'est jamais persistée.
 * Un 4e test (chemin heureux) prouve que le correctif n'a pas cassé le cas normal.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Construction directe (pas de ToolFactory dans ce module - même convention que OpenRouterProviderPrivacyTest.php). */
function makeStaleTool(string $slugSuffix, string $description = 'Ancienne description à conserver.'): Tool
{
    config(['app.locale' => 'fr_CA']);
    $slug = 'reenrich-test-'.$slugSuffix.'-'.uniqid();

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'SceneNote Test');
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', $description);
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé existant.');
    $tool->url = 'https://scenenote-test.example';
    $tool->pricing = 'freemium';
    $tool->status = 'published';
    $tool->last_enriched_at = now()->subMonths(6);
    $tool->save();

    return $tool;
}

beforeEach(function () {
    config(['directory.openrouter_api_key' => 'test-key']);
});

it('ne s\'exécute pas quand DIRECTORY_REENRICH_STALE_ENABLED est absent (défaut false) - aucune écriture, aucun appel réseau', function () {
    config(['directory.reenrich_stale.enabled' => false]);
    Http::fake();
    $tool = makeStaleTool('desactive');

    $this->artisan('tools:reenrich-stale')->assertSuccessful();

    expect($tool->fresh()->getTranslation('description', 'fr_CA', false))->toBe('Ancienne description à conserver.');
    Http::assertNothingSent();
});

it('conserve la fiche existante quand la recherche sonar-pro ne donne rien', function () {
    config(['directory.reenrich_stale.enabled' => true]);
    $tool = makeStaleTool('recherche-vide');

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '']]]], 200),
    ]);

    // Exit code 1 (FAILURE) attendu : comportement préexistant et inchangé de la commande
    // (0 succès + >0 échecs => FAILURE) - ce test prouve seulement que la fiche n'est pas
    // réécrite, pas que la commande se termine avec succès.
    $this->artisan('tools:reenrich-stale', ['--force' => true])->assertExitCode(1);

    expect($tool->fresh()->getTranslation('description', 'fr_CA', false))->toBe('Ancienne description à conserver.');
});

it('conserve la fiche existante quand la description générée affirme une absence (porte de qualité)', function () {
    config(['directory.reenrich_stale.enabled' => true]);
    $tool = makeStaleTool('absence-affirmee');

    $callCount = 0;
    Http::fake(function () use (&$callCount) {
        $callCount++;
        // 1er appel = recherche (sonar-pro), 2e = rédaction (qwen3-max) : le 2e reproduit la
        // faute exacte constatée sur SceneNote le 2026-08-14.
        if ($callCount === 1) {
            return Http::response(['choices' => [['message' => ['content' => 'Résultat de recherche générique sur SceneNote Test.']]]], 200);
        }

        $bad = str_repeat('Description généraliste sur cet outil. ', 6)
            ."Aucune version officielle de cet outil ne dispose d'un site web dédié.";

        return Http::response(['choices' => [['message' => ['content' => $bad]]]], 200);
    });

    // Même remarque que le test précédent : exit code 1 (FAILURE) attendu, comportement
    // préexistant inchangé.
    $this->artisan('tools:reenrich-stale', ['--force' => true])->assertExitCode(1);

    expect($tool->fresh()->getTranslation('description', 'fr_CA', false))->toBe('Ancienne description à conserver.')
        ->and($tool->fresh()->enrichment_version)->toBe(1);
});

it('met à jour la fiche quand la recherche et la rédaction produisent une description conforme (chemin heureux préservé)', function () {
    config(['directory.reenrich_stale.enabled' => true]);
    $tool = makeStaleTool('chemin-heureux');

    $callCount = 0;
    Http::fake(function () use (&$callCount) {
        $callCount++;
        if ($callCount === 1) {
            return Http::response(['choices' => [['message' => ['content' => 'SceneNote Test est disponible sur https://scenenote-test.example, avec un plan gratuit et un plan Pro.']]]], 200);
        }

        $good = "## À propos de SceneNote Test\n".str_repeat('SceneNote Test aide les scénaristes à organiser leurs idées efficacement. ', 6)
            ."\n\n## Fonctionnalités principales\n".str_repeat('Fonctionnalité utile pour la rédaction collaborative. ', 6)
            ."\n\n## Tarification\n".str_repeat('Le plan gratuit couvre l\'essentiel, le plan Pro ajoute des options avancées. ', 6)
            ."\n\n## Cas d'utilisation\n".str_repeat('Utilisé par des équipes de scénarisation au quotidien. ', 6)
            ."\n\n## Notre avis\n".str_repeat('Un outil solide et bien pensé pour ce public. ', 6);

        return Http::response(['choices' => [['message' => ['content' => $good]]]], 200);
    });

    $this->artisan('tools:reenrich-stale', ['--force' => true])->assertSuccessful();

    $fresh = $tool->fresh();
    expect($fresh->getTranslation('description', 'fr_CA', false))->toContain('SceneNote Test aide les scénaristes')
        ->and($fresh->enrichment_version)->toBe(2);
});
