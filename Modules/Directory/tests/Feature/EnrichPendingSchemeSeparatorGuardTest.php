<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2289 (2026-09-05) : quand le pipeline IA de tools:enrich-pending rédige une
 * description dont un lien markdown porte un espace insécable entre `https` et `://`
 * (`Str::markdown()` casse alors le schéma - href résolu comme un chemin RELATIF), la
 * fiche écrite en base doit ressortir NORMALISÉE.
 *
 * DEUXIÈME TOUR (même date) : la garde ne vit PLUS dans cette commande - elle a été déplacée
 * sur Modules/Directory/app/Observers/ToolObserver::saving() (un recensement a montré une
 * douzaine d'autres appelants qui écrivaient les mêmes champs sans passer par cette commande).
 * Ce test continue de couvrir CE chemin d'écriture précis (tools:enrich-pending), mais la
 * normalisation qu'il observe est désormais produite par l'observer, pas par un appel local.
 * Voir ToolObserverSchemeSeparatorGuardTest.php pour la preuve sur un chemin qui n'était PAS
 * protégé au premier tour (l'édition administrative).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeSchemeGuardTestTool(string $slugSuffix): Tool
{
    config(['app.locale' => 'fr_CA']);
    $slug = 'scheme-guard-test-'.$slugSuffix.'-'.uniqid();

    $tool = new Tool();
    $tool->url = 'https://scheme-guard-test.example';
    $tool->pricing = 'free';
    $tool->status = 'pending';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Scheme Guard Test');
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description initiale courte.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé initial.');
    $tool->save();

    return $tool;
}

/** Corps assez long (seuil 200 car.) contenant la jonction cassée dans la CIBLE du markdown. */
function schemeGuardBrokenBody(): string
{
    $lien = "[le site officiel](https\u{00A0}://exemple-scheme-guard.com)";

    return "## À propos de Outil Scheme Guard Test\n"
        .str_repeat('Texte de démonstration suffisamment long pour dépasser le seuil minimal requis. ', 6)
        .$lien;
}

beforeEach(function () {
    config([
        'directory.openrouter_api_key' => 'test-key',
        'directory.openrouter_writer_models' => ['modele-test/ok'],
    ]);
});

it('normalise en base la jonction schéma/séparateur écrite par le pipeline IA', function () {
    $tool = makeSchemeGuardTestTool('normalise');

    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        $model = $request->data()['model'] ?? null;
        if ($model === 'perplexity/sonar-pro') {
            return Http::response(['choices' => [['message' => ['content' => 'Résultat de recherche générique.']]]], 200);
        }

        return Http::response(['choices' => [['message' => ['content' => schemeGuardBrokenBody()]]]], 200);
    });

    $this->artisan('tools:enrich-pending', ['--id' => $tool->id])->assertExitCode(0);

    $description = $tool->fresh()->getTranslation('description', 'fr_CA', false);

    expect($description)->not->toContain("https\u{00A0}://")
        ->and($description)->toContain('https://exemple-scheme-guard.com');
});
