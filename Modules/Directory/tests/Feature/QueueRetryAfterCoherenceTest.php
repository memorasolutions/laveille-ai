<?php

declare(strict_types=1);

use Modules\Directory\Jobs\CaptureScreenshotJob;

// Les tests de Modules/ n heritent pas automatiquement du TestCase de l application :
// sans cette ligne, base_path() et config() n existent pas.
uses(Tests\TestCase::class);

// Panne silencieuse du 23 au 25 août 2026 : 15 échecs de CaptureScreenshotJob, tous en
// MaxAttemptsExceededException, aucun avec une erreur métier, et un journal vide.
//
// Cause : `retry_after` de la connexion `database` valait 90 secondes, alors que le worker des
// captures tourne avec `--timeout=270`. Un job dépassant 90 secondes était donc remis en file
// PENDANT qu'il s'exécutait encore ; la reprise voyait un compteur de tentatives déjà consommé
// (le job déclare `$tries = 1`) et échouait aussitôt. Aucune exception métier n'était levée,
// ce qui rendait la panne invisible dans les journaux.
//
// La règle de Laravel est explicite : `retry_after` doit être SUPÉRIEUR au plus long timeout de
// worker de la même connexion. Ce test la verrouille, dans les deux sens : quelqu'un qui
// rabaisserait `retry_after` OU qui allongerait le timeout du worker casse la suite.

// ⚠ LIMITE CONNUE DE CE TEST, mesurée le 2026-08-26 par une seconde panne.
//
// Ce test ne lit que le planificateur du dépôt. Or la file `screenshots` est AUSSI consommée par
// un worker déclaré en cron cPanel, donc INVISIBLE depuis le code :
//
//   * * * * * ... queue:work database --queue=cloudflare,screenshots,news-tools,workflows
//                 --stop-when-empty --max-time=50
//
// Ce cron n'avait AUCUN `--timeout`, donc Laravel appliquait son défaut de 60 secondes, et tuait
// les captures (jusqu'à 3 x 90 s) en pleine attente du processus Node : TimeoutExceededException,
// levée depuis stream_select(). Corrigé le 2026-08-26 en ajoutant `--timeout=270` au cron.
//
// La leçon : un test de cohérence ne peut pas prouver l'absence d'un consommateur qu'il ne voit
// pas. Toute modification des délais de cette file DOIT s'accompagner d'une relecture des crons
// de production (`cpanel_cron_list`), que ce fichier ne peut pas automatiser.

it('garde retry_after superieur au plus long timeout de worker de la connexion database', function () {
    $retryAfter = (int) config('queue.connections.database.retry_after');

    $provider = file_get_contents(
        base_path('Modules/Directory/app/Providers/DirectoryServiceProvider.php')
    );

    expect($provider)->toContain('--queue=screenshots');

    preg_match('/--queue=screenshots[^\']*--timeout=(\d+)/', $provider, $m);

    expect($m)->not->toBeEmpty(
        'Le worker des captures doit déclarer un --timeout explicite, sinon la cohérence '
        .'avec retry_after ne peut plus être vérifiée.'
    );

    $timeoutWorker = (int) $m[1];

    expect($retryAfter)->toBeGreaterThan(
        $timeoutWorker,
        "retry_after ({$retryAfter} s) doit dépasser le --timeout du worker de captures "
        ."({$timeoutWorker} s). Sinon un job encore en cours est remis en file et sa reprise "
        .'échoue en MaxAttemptsExceeded, sans qu aucune erreur métier ne soit enregistrée.'
    );
});

// Le --timeout passé en ligne de commande prime sur la propriété du job. Annoncer dans le job une
// valeur plus grande que celle du worker donne une fausse impression de marge : la capture serait
// tuée bien avant. Les deux doivent rester alignés.
it('garde le timeout du job aligne sur celui du worker', function () {
    $provider = file_get_contents(
        base_path('Modules/Directory/app/Providers/DirectoryServiceProvider.php')
    );

    preg_match('/--queue=screenshots[^\']*--timeout=(\d+)/', $provider, $m);
    $timeoutWorker = (int) ($m[1] ?? 0);

    $job = new CaptureScreenshotJob(new Modules\Directory\Models\Tool);

    expect($job->timeout)->toBeLessThanOrEqual(
        $timeoutWorker,
        "Le \$timeout du job ({$job->timeout} s) ne doit pas dépasser celui du worker "
        ."({$timeoutWorker} s), qui prime de toute façon."
    );
});
