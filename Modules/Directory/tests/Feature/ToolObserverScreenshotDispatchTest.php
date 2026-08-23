<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/**
 * La capture d'écran d'un outil publié doit être DISPATCHÉE, jamais exécutée dans le save().
 *
 * Ce test existe à cause d'une seconde panne réelle, le jour même où la première avait été
 * corrigée. Le 2026-08-23 à 13h38 heure du Québec (17:38 UTC), une alerte « EnrichToolJob has
 * timed out » est arrivée. Sa trace ne pointait pas du tout vers la cascade OpenRouter, seule
 * prise en compte le matin dans le calcul du délai : elle pointait vers ScreenshotService,
 * appelé par CET observateur, à l'intérieur du save() de la commande d'enrichissement.
 *
 * L'arithmétique manquante : captureWithRetry fait 3 tentatives de Process::timeout(90)
 * séparées par des pauses de 2 s puis 4 s, soit 276 secondes au pire - davantage, à elle seule,
 * que les 270 secondes accordées au job entier. Un délai calculé sur un modèle INCOMPLET de ce
 * que fait le job est un délai faux, et il le restera à chaque nouvel effet de bord ajouté.
 *
 * D'où ce test : il ne vérifie pas un nombre, il vérifie que le coût lourd a bien QUITTÉ le
 * chemin synchrone. Il échouera si quelqu'un remet une capture en direct dans l'observateur.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Directory\Jobs\CaptureScreenshotJob;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Construction directe (pas de ToolFactory dans ce module - même convention que les tests voisins). */
function makeObserverScreenshotTool(string $suffixe, array $attributs = []): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->url = 'https://observer-screenshot-test.example';
    $tool->pricing = 'free';
    $tool->status = 'pending';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Observateur Capture');
    $tool->setTranslation('slug', 'fr_CA', 'observer-capture-'.$suffixe.'-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description initiale.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé initial.');

    foreach ($attributs as $cle => $valeur) {
        $tool->{$cle} = $valeur;
    }

    $tool->save();

    return $tool;
}

it('met la capture en file d\'attente quand un outil passe à publié, au lieu de la lancer sur place', function () {
    $tool = makeObserverScreenshotTool('publie');

    Queue::fake();

    $tool->status = 'published';
    $tool->save();

    Queue::assertPushed(CaptureScreenshotJob::class, fn (CaptureScreenshotJob $job): bool => $job->tool->is($tool));
});

it('poste la capture sur la file screenshots, qui a un consommateur', function () {
    // Le point n'est pas cosmétique : le 2026-08-23, CINQ files de ce site n'avaient aucun
    // travailleur, dont des purges Cloudflare en attente depuis le 25 mai. Déplacer un travail
    // vers une file sans consommateur remplacerait une panne bruyante par une panne muette.
    expect((new CaptureScreenshotJob(makeObserverScreenshotTool('file')))->queue)->toBe('screenshots');
});

it('ne met rien en file quand la capture de l\'outil est verrouillée', function () {
    $tool = makeObserverScreenshotTool('verrouille', ['screenshot_locked' => true]);

    Queue::fake();

    $tool->status = 'published';
    $tool->save();

    Queue::assertNotPushed(CaptureScreenshotJob::class);
});

it('ne met rien en file quand l\'outil porte déjà une capture locale', function () {
    $tool = makeObserverScreenshotTool('deja', ['screenshot' => 'screenshots/deja-capture.jpg']);

    Queue::fake();

    $tool->status = 'published';
    $tool->save();

    Queue::assertNotPushed(CaptureScreenshotJob::class);
});

it('ne remet rien en file lors d\'une écriture qui ne change pas le statut', function () {
    // La simulation est armée AVANT la publication, sinon le premier envoi part pour de vrai
    // (connexion `sync` en test) et exécute le job - ce qui a fait remonter, au passage, un
    // TypeError bien réel dans ScreenshotService::isAvailable().
    Queue::fake();

    $tool = makeObserverScreenshotTool('inchange');
    $tool->status = 'published';
    $tool->save();

    Queue::assertPushed(CaptureScreenshotJob::class, 1);

    $tool->setTranslation('short_description', 'fr_CA', 'Résumé modifié.');
    $tool->save();

    // Toujours UN seul envoi : une écriture sans changement de statut ne redéclenche rien.
    Queue::assertPushed(CaptureScreenshotJob::class, 1);
});

it('ne lève plus d\'erreur quand le chemin de Node n\'est pas configuré', function () {
    // La clé EXISTE et vaut null quand la variable d'environnement est absente : le 2e argument
    // de config() ne s'applique donc JAMAIS, et `file_exists(null)` levait un TypeError au lieu
    // de renvoyer false. Mesuré le 2026-08-23.
    config()->set('services.browsershot.node_path', null);

    expect(\Modules\Directory\Services\ScreenshotService::isAvailable())->toBeBool();
});
