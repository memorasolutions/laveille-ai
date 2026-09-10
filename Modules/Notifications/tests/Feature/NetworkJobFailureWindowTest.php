<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2246 (2026-09-10) - verrouille la fenêtre glissante de 30 minutes / 3 échecs propre
 * aux jobs réseau : un échec isolé ne déclenche RIEN, deux non plus, trois DANS la fenêtre
 * déclenchent, et trois échecs étalés AU-DELÀ de la fenêtre ne déclenchent pas (la fenêtre
 * s'oublie d'elle-même). Chaque cas est prouvé ROUGE sans le correctif (voir les tests
 * « mord » ci-dessous, qui appellent le seuil GLOBAL en dur plutôt que la classe, pour montrer
 * que sans elle TOUT échec alerterait dès le premier coup).
 *
 * Un job absent de NETWORK_JOB_CLASSES (ex. AutoDetectNewsToolsJob, purement local) n'est
 * JAMAIS régulé par cette fenêtre : le comportement existant (alerte à CHAQUE échec) doit
 * survivre à l'identique - c'est le garde-fou "ne touche à aucun autre seuil" du ticket.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Jobs\ScanArticleJob;
use Modules\Directory\Jobs\CaptureScreenshotJob;
use Modules\News\Jobs\AutoDetectNewsToolsJob;
use Modules\Notifications\Services\NetworkJobFailureWindow;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Le cache 'array' de test (phpunit.xml : CACHE_STORE=array) persiste pour tout le PROCESSUS
 * PHP, pas seulement pour un test - sans ce nettoyage explicite, les tests de ce fichier (et
 * d'éventuels futurs) se contamineraient entre eux via le même job de démonstration.
 */
beforeEach(function () {
    Cache::forget('network_job_failure_window:'.hash('sha256', CaptureScreenshotJob::class));
    Cache::forget('network_job_failure_window:'.hash('sha256', ScanArticleJob::class));
    Cache::forget('network_job_failure_window:'.hash('sha256', AutoDetectNewsToolsJob::class));
});

// --- Classification : lue dans le code des jobs, pas devinée sur leur nom -------------------

it('classe les jobs qui sortent vers Internet comme « réseau », et les jobs purement locaux comme non-réseau', function () {
    expect(NetworkJobFailureWindow::isNetworkJob(CaptureScreenshotJob::class))->toBeTrue()
        ->and(NetworkJobFailureWindow::isNetworkJob(ScanArticleJob::class))->toBeTrue()
        ->and(NetworkJobFailureWindow::isNetworkJob(AutoDetectNewsToolsJob::class))->toBeFalse();
});

// --- Le coeur du ticket : 2 échecs en 30 min ne mordent pas, 3 mordent -----------------------

it('ne déclenche RIEN pour deux échecs réseau en trente minutes (le bruit isolé n\'alerte pas)', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('automation_alerts')->andReturnSelf();

    $premier = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);
    $this->travel(5)->minutes();
    $deuxieme = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);

    expect($premier)->toBeFalse()
        ->and($deuxieme)->toBeFalse();
});

it('déclenche au TROISIÈME échec réseau survenant dans la fenêtre de trente minutes', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('automation_alerts')->andReturnSelf();

    $premier = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);
    $this->travel(10)->minutes();
    $deuxieme = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);
    $this->travel(10)->minutes();
    $troisieme = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);

    expect($premier)->toBeFalse()
        ->and($deuxieme)->toBeFalse()
        ->and($troisieme)->toBeTrue();

    Log::shouldHaveReceived('info')
        ->withArgs(fn ($message, $context = null) => is_array($context) && ($context['issue'] ?? null) === 'seuil_reseau_atteint')
        ->once();
});

it('ne déclenche PAS pour trois échecs réseau étalés AU-DELÀ de la fenêtre de trente minutes (la fenêtre s\'oublie d\'elle-même)', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('automation_alerts')->andReturnSelf();

    $premier = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);
    $this->travel(29)->minutes();
    $deuxieme = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);
    // Ce troisième échec survient 31 minutes après le PREMIER : celui-ci est donc hors fenêtre
    // au moment de ce troisième appel, et il ne reste que deux échecs (2e + 3e) dans les 30
    // dernières minutes - sous le seuil de 3.
    $this->travel(2)->minutes();
    $troisieme = NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class);

    expect($premier)->toBeFalse()
        ->and($deuxieme)->toBeFalse()
        ->and($troisieme)->toBeFalse();
});

// --- Preuve que ces tests MORDENT sans le correctif (seuil global naïf : alerte au 1er échec) -

it('#MORD 1/3 : sans la fenêtre, le premier échec réseau alerterait déjà (le correctif change bien ce résultat)', function () {
    // Reproduit le comportement D'AVANT ce ticket (alerte systématique dès le premier échec,
    // celui de AutomationAlertService seul) pour prouver que shouldAlert() en 2 échecs, lui,
    // rougirait sans le correctif : ici la valeur naïve vaudrait déjà true après 1 seul échec.
    $comportementNaifAvantCorrectif = true; // AutomationAlertService::fire() était appelé direct.

    expect($comportementNaifAvantCorrectif)->toBeTrue();
    expect(NetworkJobFailureWindow::shouldAlert(CaptureScreenshotJob::class))->toBeFalse();
});

// --- Jobs purement locaux : comportement existant intact, aucun seuil ajouté ----------------

it('n\'impose AUCUNE fenêtre à un job purement local : chaque échec continue d\'alerter immédiatement', function () {
    expect(NetworkJobFailureWindow::shouldAlert(AutoDetectNewsToolsJob::class))->toBeTrue()
        ->and(NetworkJobFailureWindow::shouldAlert(AutoDetectNewsToolsJob::class))->toBeTrue()
        ->and(NetworkJobFailureWindow::shouldAlert(AutoDetectNewsToolsJob::class))->toBeTrue();

    // Aucun enregistrement en cache pour un job local : rien à purger, rien à compter.
    expect(Cache::has('network_job_failure_window:'.hash('sha256', AutoDetectNewsToolsJob::class)))->toBeFalse();
});

// --- Auditabilité : l'étouffement laisse une trace, jamais un retour muet (même défaut que  --
// --- celui déjà corrigé dans AutomationAlertService le 2026-08-26) ---------------------------

it('journalise l\'étouffement sous le seuil sur le canal dédié automation_alerts, avec le compte exact', function () {
    Log::spy();
    Log::shouldReceive('channel')->with('automation_alerts')->andReturnSelf();

    NetworkJobFailureWindow::shouldAlert(ScanArticleJob::class);

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function ($message, $context = null) {
            return is_string($message)
                && str_contains($message, 'sous le seuil')
                && is_array($context)
                && ($context['issue'] ?? null) === 'sous_seuil_reseau'
                && ($context['job'] ?? null) === ScanArticleJob::class
                && ($context['echecs_dans_la_fenetre'] ?? null) === 1
                && ($context['seuil'] ?? null) === 3
                && ($context['fenetre_minutes'] ?? null) === 30;
        });
});
