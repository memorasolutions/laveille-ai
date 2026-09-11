<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Mandat sécurité (identifié le 2026-08-25, posé le 2026-09-10) - durcissement de
 * public/_lvgit.php, le point d'entrée de secours qui resynchronise la prod avec
 * origin/master quand le Shell API cPanel est indisponible. Décision : le fichier reste
 * (seul filet si la chaîne de déploiement tombe), mais deux défauts sont corrigés :
 *
 * DÉFAUT 1 : le jeton voyageait dans la chaîne de requête (?t=), donc en clair dans les
 * journaux d'accès, les journaux des intermédiaires réseau et l'en-tête Referer. Corrigé :
 * le jeton voyage désormais UNIQUEMENT par l'en-tête HTTP X-Lv-Git-Token, sans aucune
 * compatibilité conservée avec ?t= (une compatibilité « au cas où » annulerait le correctif).
 *
 * DÉFAUT 2 : l'option &seed=ClassName autorisait, derrière un seul jeton, l'exécution d'une
 * classe de semence de base de données - donc une réécriture possible de données de
 * PRODUCTION. Corrigé : l'option est retirée, silencieusement sans effet.
 *
 * CE FICHIER EXÉCUTE RÉELLEMENT le script réel public/_lvgit.php (copié tel quel depuis le
 * dépôt, jamais réécrit ni simulé) dans un bac à sable totalement isolé : un dépôt git
 * jetable (origin bare + clone de travail, AUCUN réseau, jamais le dépôt du projet), servi
 * par le serveur intégré de PHP sur un port libre. Le jeton employé est FACTICE, généré par
 * le test lui-même (random_bytes) - jamais lu depuis le vrai .env, jamais imprimé, jamais
 * journalisé. Le bac à sable est détruit à chaque test (beforeEach/afterEach), jamais
 * partagé, jamais laissé sur disque.
 *
 * Preuve du rouge/vert : voir le rapport de session - la suite a été rejouée avec chacun des
 * deux correctifs retiré séparément (patch inverse temporaire) pour confirmer que CHAQUE
 * test échoue pour SA propre raison, puis avec le fichier réellement corrigé restauré pour
 * confirmer le vert complet.
 */

use Symfony\Component\Process\Process;

/**
 * Construit un bac à sable jetable et démarre le serveur intégré de PHP dessus.
 *
 * @return array{base: string, token: string, process: Process, sandbox: string}
 */
function lvGitBootSandbox(): array
{
    $sandbox = sys_get_temp_dir().'/lvgit-test-'.bin2hex(random_bytes(6));
    mkdir($sandbox, 0700, true);

    $originDir = $sandbox.'/origin.git';
    $workDir = $sandbox.'/work';

    lvGitRunOrFail(['/usr/bin/git', 'init', '--quiet', '--bare', $originDir], $sandbox);
    lvGitRunOrFail(['/usr/bin/git', 'clone', '--quiet', $originDir, $workDir], $sandbox);
    lvGitRunOrFail(['/usr/bin/git', '-C', $workDir, 'config', 'user.email', 'test@example.invalid'], $sandbox);
    lvGitRunOrFail(['/usr/bin/git', '-C', $workDir, 'config', 'user.name', 'Bac a sable de test'], $sandbox);
    file_put_contents($workDir.'/README.md', "bac a sable jetable\n");
    lvGitRunOrFail(['/usr/bin/git', '-C', $workDir, 'add', '-A'], $sandbox);
    lvGitRunOrFail(['/usr/bin/git', '-C', $workDir, 'commit', '--quiet', '-m', 'init'], $sandbox);
    lvGitRunOrFail(['/usr/bin/git', '-C', $workDir, 'branch', '-M', 'master'], $sandbox);
    lvGitRunOrFail(['/usr/bin/git', '-C', $workDir, 'push', '--quiet', '-u', 'origin', 'master'], $sandbox);

    mkdir($workDir.'/public', 0700, true);
    copy(dirname(__DIR__, 2).'/public/_lvgit.php', $workDir.'/public/_lvgit.php');

    // Jeton FACTICE généré ici même, jamais la vraie valeur de .env - jamais imprimé/journalisé.
    $fakeToken = bin2hex(random_bytes(32));
    file_put_contents($workDir.'/.env', "LV_GIT_TOKEN=$fakeToken\n");

    $port = lvGitFreePort();
    $process = new Process(['php', '-S', "127.0.0.1:{$port}", '-t', $workDir.'/public']);
    $process->setTimeout(null);
    $process->start();

    lvGitWaitForServer('127.0.0.1', $port, $process, $sandbox);

    return [
        'base' => "http://127.0.0.1:{$port}",
        'token' => $fakeToken,
        'process' => $process,
        'sandbox' => $sandbox,
    ];
}

function lvGitRunOrFail(array $cmd, string $sandbox): void
{
    $process = new Process($cmd);
    $process->run();
    if (! $process->isSuccessful()) {
        lvGitCleanupDir($sandbox);
        throw new RuntimeException(
            'Commande de préparation du bac à sable échouée: '.implode(' ', $cmd)."\n".$process->getErrorOutput()
        );
    }
}

function lvGitFreePort(): int
{
    $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($socket === false) {
        throw new RuntimeException("Impossible de réserver un port libre pour le bac à sable: {$errstr}");
    }
    $name = stream_socket_get_name($socket, false);
    fclose($socket);
    $parts = explode(':', (string) $name);

    return (int) end($parts);
}

function lvGitWaitForServer(string $host, int $port, Process $process, string $sandbox): void
{
    $deadline = microtime(true) + 5.0;
    while (microtime(true) < $deadline) {
        $conn = @fsockopen($host, $port, $errno, $errstr, 0.2);
        if ($conn !== false) {
            fclose($conn);

            return;
        }
        usleep(50000);
    }
    $output = $process->getErrorOutput();
    lvGitCleanupDir($sandbox);
    throw new RuntimeException("Le serveur PHP intégré du bac à sable n'a jamais répondu sur {$host}:{$port}. Sortie: {$output}");
}

function lvGitShutdownSandbox(array $sandboxInfo): void
{
    $process = $sandboxInfo['process'];
    if ($process->isRunning()) {
        $process->stop(2);
    }
    lvGitCleanupDir($sandboxInfo['sandbox']);
}

function lvGitCleanupDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $fileInfo) {
        $fileInfo->isDir() ? @rmdir($fileInfo->getPathname()) : @unlink($fileInfo->getPathname());
    }
    @rmdir($dir);
}

/**
 * Requête HTTP minimale vers l'endpoint du bac à sable, avec en-tête optionnel.
 *
 * @return array{0: int, 1: string} [statut HTTP, corps de la réponse]
 */
function lvGitCall(string $base, string $path, ?string $headerToken = null): array
{
    $headers = [];
    if ($headerToken !== null) {
        $headers[] = "X-Lv-Git-Token: {$headerToken}";
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
            'timeout' => 5,
        ],
    ]);
    $body = @file_get_contents($base.$path, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
            $status = (int) $m[1];
        }
    }

    return [$status, (string) $body];
}

beforeEach(function () {
    $this->lvGitSandbox = lvGitBootSandbox();
});

afterEach(function () {
    lvGitShutdownSandbox($this->lvGitSandbox);
});

test('un jeton transmis dans la chaîne de requête (?t=) est REFUSÉ - défaut 1', function () {
    $sandbox = $this->lvGitSandbox;

    [$status, $body] = lvGitCall($sandbox['base'], '/_lvgit.php?t='.$sandbox['token']);

    expect($status)->toBe(403);
    expect($body)->toBe('forbidden');
});

test('le jeton transmis par en-tête X-Lv-Git-Token est ACCEPTÉ', function () {
    $sandbox = $this->lvGitSandbox;

    [$status, $body] = lvGitCall($sandbox['base'], '/_lvgit.php', $sandbox['token']);

    expect($status)->toBe(200);
    expect($body)->toContain('OK ');
});

test("une tentative d'exécution de semence via &seed= est REFUSÉE - défaut 2", function () {
    $sandbox = $this->lvGitSandbox;

    // 'Database\Seeders\DatabaseSeeder' était, AVANT le correctif, une classe explicitement
    // AUTORISÉE par l'allowlist (préfixe Database\Seeders\) : valeur choisie à dessein pour
    // prouver que le retrait est total, pas partiel.
    [$status, $body] = lvGitCall(
        $sandbox['base'],
        '/_lvgit.php?seed='.rawurlencode('Database\\Seeders\\DatabaseSeeder'),
        $sandbox['token']
    );

    expect($status)->toBe(200);
    expect($body)->not->toContain('db:seed');
    expect($body)->not->toContain('seed skipped');
});

test("l'usage légitime (en-tête valide, sans paramètre dangereux) reste opérationnel", function () {
    $sandbox = $this->lvGitSandbox;

    [$status, $body] = lvGitCall($sandbox['base'], '/_lvgit.php', $sandbox['token']);

    expect($status)->toBe(200);
    expect($body)->toContain('$ /usr/bin/git fetch --quiet origin');
    expect($body)->toContain('$ /usr/bin/git reset --hard origin/master');
    expect($body)->toContain('$ /usr/bin/git log -1 --oneline');
    expect($body)->toContain('$ /usr/bin/git status -s');
    expect($body)->not->toContain('[proc_open failed]');
    expect($body)->toMatch('/OK \d{4}-\d{2}-\d{2}T/');
});
