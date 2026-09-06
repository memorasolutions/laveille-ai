<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2289 (2026-09-05) : tools:repair-scheme-separator, la porte de RATTRAPAGE des fiches
 * déjà écrites en base avant que la garde (Modules/Directory/app/Observers/ToolObserver::saving(),
 * voir ToolObserverSchemeSeparatorGuardTest.php) ne ferme le trou à l'écriture, pour TOUT
 * appelant. Modèle suivi : news:retire (sauvegarde AVANT mutation, --dry-run explicite, --restore
 * réversible).
 *
 * --restore est testé avec Tool::withoutEvents() actif dans la commande elle-même : sans ce
 * contournement DÉLIBÉRÉ de ToolObserver, restaurer la valeur défectueuse serait immédiatement
 * re-corrigé par la garde au moment du save(), et --restore ne pourrait plus jamais reproduire
 * l'état d'avant (voir docblock de RepairSchemeSeparatorCommand::restaurer()).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Fiche dont la description est DÉJÀ corrompue en base - simule les données d'avant correctif.
 *
 * Tool::withoutEvents() est OBLIGATOIRE ici : ToolObserver::saving() répare désormais TOUTE
 * écriture de ces champs. Sans ce contournement, la valeur cassée passée en paramètre serait
 * normalisée dès ce save() de préparation - le test n'aurait alors plus rien à détecter ni à
 * corriger, et masquerait une régression de tools:repair-scheme-separator lui-même.
 */
function makeRepairTestTool(string $slugSuffix, string $description, string $shortDescription = 'Résumé sain.'): Tool
{
    config(['app.locale' => 'fr_CA']);
    $slug = 'repair-scheme-test-'.$slugSuffix.'-'.uniqid();

    return Tool::withoutEvents(function () use ($slug, $description, $shortDescription) {
        $tool = new Tool();
        $tool->url = 'https://repair-scheme-test.example';
        $tool->pricing = 'free';
        $tool->status = 'published';
        $tool->is_featured = false;
        $tool->setTranslation('name', 'fr_CA', 'Outil Repair Scheme Test');
        $tool->setTranslation('slug', 'fr_CA', $slug);
        $tool->setTranslation('description', 'fr_CA', $description);
        $tool->setTranslation('short_description', 'fr_CA', $shortDescription);
        $tool->save();

        return $tool;
    });
}

/** Ajoute (sans passer par la garde - même raison que ci-dessus) une traduction cassée sur une locale donnée. */
function corromptTraduction(Tool $tool, string $champ, string $locale, string $valeur): void
{
    Tool::withoutEvents(function () use ($tool, $champ, $locale, $valeur) {
        $tool->setTranslation($champ, $locale, $valeur);
        $tool->save();
    });
}

afterEach(function () {
    // Nettoyage des fichiers de sauvegarde produits pendant les tests.
    foreach (glob(storage_path('app/directory-repair-scheme-separator-backup-*.json')) ?: [] as $fichier) {
        @unlink($fichier);
    }
});

it('--dry-run affiche le compte sans rien écrire ni créer de sauvegarde', function () {
    $casse = "Voir [le site](https\u{00A0}://exemple-repair.com) pour en savoir plus.";
    $tool = makeRepairTestTool('dry-run', $casse);

    $avantBackups = glob(storage_path('app/directory-repair-scheme-separator-backup-*.json')) ?: [];

    $this->artisan('tools:repair-scheme-separator', ['--dry-run' => true])
        ->expectsOutputToContain('1 champ(s) seraient corrigés')
        ->expectsOutputToContain('seraient corrigées')
        ->assertExitCode(0);

    $apresBackups = glob(storage_path('app/directory-repair-scheme-separator-backup-*.json')) ?: [];
    expect($apresBackups)->toHaveCount(count($avantBackups));

    // Rien n'a été écrit : la valeur cassée est toujours en base, intacte.
    expect($tool->fresh()->getTranslation('description', 'fr_CA', false))->toBe($casse);
});

it('sans --dry-run, corrige en base, écrit une sauvegarde AVANT mutation, et le rapport dénombre juste', function () {
    $casse = "Voir [le site](https\u{00A0}://exemple-repair.com) pour en savoir plus.";
    $tool = makeRepairTestTool('apply', $casse);

    $this->artisan('tools:repair-scheme-separator')
        ->expectsOutputToContain('1 champ(s) corrigés')
        ->expectsOutputToContain('Corrigé : 1 ligne(s).')
        ->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->getTranslation('description', 'fr_CA', false))
        ->toBe('Voir [le site](https://exemple-repair.com) pour en savoir plus.')
        ->not->toContain("\u{00A0}://");

    $backups = glob(storage_path('app/directory-repair-scheme-separator-backup-*.json')) ?: [];
    expect($backups)->toHaveCount(1);

    $contenu = json_decode((string) File::get($backups[0]), true);
    expect($contenu)->toHaveCount(1)
        ->and($contenu[0]['id'])->toBe($tool->id)
        ->and($contenu[0]['champ'])->toBe('description')
        ->and($contenu[0]['locale'])->toBe('fr_CA')
        ->and($contenu[0]['avant'])->toBe($casse);
});

it('détecte et corrige la jonction cassée sur une locale AUTRE que fr_CA (« fr » ou « en »)', function () {
    $casse = "Official site: [link](https\u{00A0}://exemple-repair-en.com) for details.";
    $tool = makeRepairTestTool('locale-en', 'Description fr_CA saine, sans problème.');
    corromptTraduction($tool, 'description', 'en', $casse);

    $this->artisan('tools:repair-scheme-separator', ['--dry-run' => true])
        ->expectsOutputToContain('1 champ(s) seraient corrigés')
        ->assertExitCode(0);

    $this->artisan('tools:repair-scheme-separator')->assertExitCode(0);

    $fresh = $tool->fresh();
    expect($fresh->getTranslation('description', 'en', false))
        ->toBe('Official site: [link](https://exemple-repair-en.com) for details.')
        ->and($fresh->getTranslation('description', 'fr_CA', false))
        ->toBe('Description fr_CA saine, sans problème.');

    $backups = glob(storage_path('app/directory-repair-scheme-separator-backup-*.json')) ?: [];
    expect($backups)->toHaveCount(1);
    $contenu = json_decode((string) File::get($backups[0]), true);
    expect($contenu[0]['locale'])->toBe('en');
});

it('--restore ramène la valeur d\'AVANT correction à partir de la sauvegarde', function () {
    $casse = "Voir [le site](https\u{00A0}://exemple-repair.com) pour en savoir plus.";
    $tool = makeRepairTestTool('restore', $casse);

    $this->artisan('tools:repair-scheme-separator')->assertExitCode(0);

    $backups = glob(storage_path('app/directory-repair-scheme-separator-backup-*.json')) ?: [];
    expect($backups)->toHaveCount(1);
    expect($tool->fresh()->getTranslation('description', 'fr_CA', false))->not->toBe($casse);

    $this->artisan('tools:repair-scheme-separator', ['--restore' => $backups[0]])
        ->expectsOutputToContain('Restauré : 1 champ(s)')
        ->assertExitCode(0);

    expect($tool->fresh()->getTranslation('description', 'fr_CA', false))->toBe($casse);
});

it('n\'affecte pas une fiche sans la jonction cassée (aucun faux positif)', function () {
    makeRepairTestTool('propre', 'Une description saine, sans aucun problème, avec https://exemple.com propre.');

    $this->artisan('tools:repair-scheme-separator', ['--dry-run' => true])
        ->expectsOutputToContain('Aucune fiche à corriger')
        ->assertExitCode(0);
});
