<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif 2026-08-23 (voir Modules/Directory/tests/Feature/OpenRouterServiceCascadeTest.php pour
 * la preuve de la cascade de modèles côté OpenRouterService) - deux garanties propres à
 * tools:enrich-pending :
 *   1. --no-publish laisse une fiche pending enrichie avec succès EN pending (relecture humaine
 *      possible avant mise en ligne) ; sans l'option, le comportement existant (publication
 *      automatique) est préservé.
 *   2. Le message affiché distingue désormais « l'API a refusé/échoué » (description vide -
 *      cascade OpenRouter épuisée) de « le modèle a répondu mais trop court » (description non
 *      vide sous le seuil de 200 caractères) - avant ce correctif, les deux cas affichaient le
 *      même « Génération trop courte », ce qui masquait la panne réelle (voir config/directory.php
 *      et Modules/Directory/app/Services/OpenRouterService.php).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Construction directe (pas de ToolFactory dans ce module - même convention que les tests voisins). */
function makeEnrichPendingTestTool(string $slugSuffix): Tool
{
    config(['app.locale' => 'fr_CA']);
    $slug = 'enrich-pending-test-'.$slugSuffix.'-'.uniqid();

    $tool = new Tool();
    $tool->url = 'https://enrich-pending-test.example';
    $tool->pricing = 'free';
    $tool->status = 'pending';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Enrich Pending Test');
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description initiale courte.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé initial.');
    $tool->save();

    return $tool;
}

/** Fiche de rédaction assez longue pour dépasser le seuil minimal (200 car.) de la commande. */
function orscLongDescriptionBody(): string
{
    return "## À propos de Outil Enrich Pending Test\n"
        .str_repeat('Texte de démonstration suffisamment long pour dépasser le seuil minimal requis par la commande. ', 6);
}

beforeEach(function () {
    config([
        'directory.openrouter_api_key' => 'test-key',
        'directory.openrouter_writer_models' => ['modele-test/ok'],
    ]);
});

/**
 * Fake sonar-pro (recherche) + un second modèle piloté par le test via $writerResponse.
 * $writerResponse est soit le résultat de Http::response() (une PromiseInterface Guzzle - c'est
 * ce que Http::response() retourne réellement, jamais une instance Response), soit une Closure
 * qui reçoit la requête et retourne l'un ou l'autre.
 */
function fakeEnrichPendingHttp(mixed $writerResponse): void
{
    Http::fake(function (\Illuminate\Http\Client\Request $request) use ($writerResponse) {
        $model = $request->data()['model'] ?? null;

        if ($model === 'perplexity/sonar-pro') {
            return Http::response(['choices' => [['message' => ['content' => 'Résultat de recherche générique.']]]], 200);
        }

        return $writerResponse instanceof \Closure ? $writerResponse($request) : $writerResponse;
    });
}

it('sans --no-publish, une fiche pending enrichie avec succès passe à published (comportement existant préservé)', function () {
    $tool = makeEnrichPendingTestTool('publie-defaut');

    fakeEnrichPendingHttp(Http::response(['choices' => [['message' => ['content' => orscLongDescriptionBody()]]]], 200));

    $this->artisan('tools:enrich-pending', ['--id' => $tool->id])
        ->expectsOutputToContain('Publié automatiquement (était pending)')
        ->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

it('avec --no-publish, une fiche pending enrichie avec succès reste pending', function () {
    $tool = makeEnrichPendingTestTool('reste-pending');

    fakeEnrichPendingHttp(Http::response(['choices' => [['message' => ['content' => orscLongDescriptionBody()]]]], 200));

    $this->artisan('tools:enrich-pending', ['--id' => $tool->id, '--no-publish' => true])
        ->doesntExpectOutputToContain('Publié automatiquement')
        ->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->status)->toBe('pending')
        ->and($fresh->getTranslation('description', 'fr_CA', false))->toContain('À propos de Outil Enrich Pending Test');
});

it('quand l\'API refuse tous les modèles (cascade épuisée), le message signale un échec API - pas "trop courte"', function () {
    $tool = makeEnrichPendingTestTool('echec-api');

    fakeEnrichPendingHttp(Http::response(['error' => ['message' => 'No endpoints found matching your data policy (Zero data retention)']], 404));

    $this->artisan('tools:enrich-pending', ['--id' => $tool->id])
        ->expectsOutputToContain("Échec de génération (l'API a refusé ou échoué")
        ->doesntExpectOutputToContain('trop courte')
        ->assertExitCode(0);

    // Fiche jamais réécrite, jamais publiée : l'échec ne doit rien altérer.
    $fresh = $tool->fresh();
    expect($fresh->getTranslation('description', 'fr_CA', false))->toBe('Description initiale courte.')
        ->and($fresh->status)->toBe('pending');
});

it('quand le modèle répond mais trop court, le message signale "trop courte" - pas un échec API', function () {
    $tool = makeEnrichPendingTestTool('trop-court');

    fakeEnrichPendingHttp(Http::response(['choices' => [['message' => ['content' => 'Réponse beaucoup trop courte.']]]], 200));

    $this->artisan('tools:enrich-pending', ['--id' => $tool->id])
        ->expectsOutputToContain('trop courte')
        ->doesntExpectOutputToContain('Échec de génération')
        ->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->getTranslation('description', 'fr_CA', false))->toBe('Description initiale courte.')
        ->and($fresh->status)->toBe('pending');
});
