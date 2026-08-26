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

// AJOUTE le 2026-08-26 apres une RECIDIVE que les deux tests ci-dessus n'ont pas su empecher.
//
// Ils verifiaient la coherence entre `retry_after`, le `--timeout` du worker et le `$timeout` du
// job - mais jamais que ce `--timeout` couvre la duree REELLE du travail. Or `captureWithRetry`
// fait 3 tentatives de 90 s ET attend entre elles (`sleep(2^n)` : 2 s puis 4 s), soit 276 s au
// minimum. Un worker cale a 270 (= 3 x 90, sans les attentes) tuait donc le job 6 secondes avant
// la fin de sa derniere tentative, avant meme de compter le demarrage du processus Node.
//
// Ce test lit les deux nombres DANS LE CODE plutot que de les recopier : il casse donc aussi si
// quelqu'un allonge le delai de Node ou ajoute une tentative sans toucher au worker.
it('garde le timeout du worker au-dessus de la duree reelle maximale du job', function () {
    $service = file_get_contents(
        base_path('Modules/Directory/app/Services/ScreenshotService.php')
    );

    preg_match('/Process::timeout\((\d+)\)/', $service, $mNode);
    expect($mNode)->not->toBeEmpty('Le delai du processus de capture doit rester lisible dans le code.');
    $delaiNode = (int) $mNode[1];

    preg_match('/captureWithRetry\([^)]*\$maxAttempts = (\d+)/', $service, $mTentatives);
    expect($mTentatives)->not->toBeEmpty('Le nombre de tentatives doit rester lisible dans le code.');
    $tentatives = (int) $mTentatives[1];

    // Attentes exponentielles entre deux tentatives : sleep(2^1) + sleep(2^2) + ...
    $attentes = 0;
    for ($i = 1; $i < $tentatives; $i++) {
        $attentes += 2 ** $i;
    }

    $dureeMaxJob = $tentatives * $delaiNode + $attentes;

    $provider = file_get_contents(
        base_path('Modules/Directory/app/Providers/DirectoryServiceProvider.php')
    );
    preg_match('/--queue=screenshots[^\']*--timeout=(\d+)/', $provider, $m);
    $timeoutWorker = (int) ($m[1] ?? 0);

    expect($timeoutWorker)->toBeGreaterThan(
        $dureeMaxJob,
        "Le --timeout du worker ({$timeoutWorker} s) doit DEPASSER la duree maximale reelle du job "
        ."({$dureeMaxJob} s = {$tentatives} tentatives de {$delaiNode} s + {$attentes} s d attentes "
        .'entre elles). Sinon le job est tue avant d avoir fini sa derniere tentative, et l echec '
        .'remonte en TimeoutExceeded sans qu aucune erreur metier ne soit enregistree.'
    );
});
