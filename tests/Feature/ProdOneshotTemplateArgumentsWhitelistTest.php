<?php

declare(strict_types=1);

/**
 * Le gabarit scripts/templates/prod-oneshot.php.tpl décode un JSON d'arguments arbitraire depuis
 * la requête ($_GET['args']) et l'injecte dans kernel->call() - seul le NOM de la commande était
 * revalidé contre une liste blanche (COMMANDES_AUTORISEES), jamais ses OPTIONS. Un jeton valide et
 * une commande autorisée ne suffisaient donc pas à empêcher un mode forcé ou une sélection
 * explicite d'identifiants d'être construits depuis l'URL, pour peu que la commande visée les
 * définisse (mesuré avec news:regenerate-fallback-images et son option --force, ajoutée cette
 * nuit-là à une copie de ce même gabarit sans que ses options soient restreintes).
 *
 * Ce test verrouille ARGUMENTS_AUTORISES (la liste blanche par commande qui ferme ce trou) :
 * toute clé de `args` absente de la liste de SA commande est un refus (403) AVANT même d'amorcer
 * Laravel, et un appel dont toutes les clés sont déclarées continue de s'exécuter normalement.
 *
 * Chaque scénario tourne dans un sous-processus PHP isolé : le gabarit appelle exit() sur ses
 * chemins de refus, ce qui terminerait le runner de test s'il tournait dans le même processus.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Support\Facades\File;

/**
 * Dépose une copie du gabarit réel, jeton substitué, à l'endroit où `__DIR__.'/../vendor/...'`
 * résout correctement (public/, comme en production et comme les instructions imprimées par
 * scripts/prod-artisan.sh) - jamais sous scripts/.scratch/, dont la profondeur casse ce chemin
 * relatif.
 */
function deployProdOneshotTemplateForTest(string $token): string
{
    $tpl = File::get(base_path('scripts/templates/prod-oneshot.php.tpl'));
    $tpl = str_replace('__TOKEN__', addcslashes($token, '\\\''), $tpl);

    $dest = public_path('_pest-oneshot-'.substr(md5(uniqid('', true)), 0, 12).'.php');
    File::put($dest, $tpl);

    return $dest;
}

/**
 * Exécute le fichier déposé dans un sous-processus PHP CLI isolé, $_GET peuplé manuellement (le
 * SAPI CLI ne le fait jamais depuis une query string) - un exit() du fichier ne termine alors que
 * ce sous-processus, jamais le runner de test.
 *
 * @return array{stdout: string, exitCode: int}
 */
function runProdOneshotTemplateForTest(string $file, array $get): array
{
    $wrapperPath = tempnam(sys_get_temp_dir(), 'oneshot-wrapper-').'.php';
    File::put($wrapperPath, "<?php\n\$_GET = json_decode(\$argv[1], true);\nrequire \$argv[2];\n");

    try {
        $getJson = json_encode($get, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $process = proc_open(
            [PHP_BINARY, $wrapperPath, $getJson, $file],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path()
        );

        if (! is_resource($process)) {
            throw new \RuntimeException('proc_open a échoué pour le sous-processus de test.');
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return ['stdout' => (string) $stdout, 'exitCode' => $exitCode];
    } finally {
        @unlink($wrapperPath);
    }
}

afterEach(function () {
    // Filet de sécurité : balaie tout fichier de test resté en public/ si un scénario précédent a
    // levé une exception avant d'atteindre son propre nettoyage.
    foreach (glob(public_path('_pest-oneshot-*.php')) ?: [] as $leftover) {
        @unlink($leftover);
    }
});

test('un argument non déclaré pour la commande visée est refusé avant même d\'amorcer Laravel', function () {
    $token = bin2hex(random_bytes(16));
    $file = deployProdOneshotTemplateForTest($token);

    try {
        $result = runProdOneshotTemplateForTest($file, [
            't' => $token,
            'cmd' => 'news:apply',
            'args' => json_encode(['article' => '999999937', '--force' => true], JSON_UNESCAPED_UNICODE),
        ]);

        expect($result['stdout'])->toContain('argument hors liste blanche : --force');
        expect($result['exitCode'])->toBe(1);
    } finally {
        @unlink($file);
    }
});

test('une sélection explicite d\'identifiants non déclarée est refusée, quelle que soit la commande', function () {
    $token = bin2hex(random_bytes(16));
    $file = deployProdOneshotTemplateForTest($token);

    try {
        $result = runProdOneshotTemplateForTest($file, [
            't' => $token,
            'cmd' => 'news:brief',
            'args' => json_encode(['article' => '999999937', '--ids' => '1,2,3'], JSON_UNESCAPED_UNICODE),
        ]);

        expect($result['stdout'])->toContain('argument hors liste blanche : --ids');
        expect($result['exitCode'])->toBe(1);
    } finally {
        @unlink($file);
    }
});

test('un appel dont toutes les clés sont déclarées continue de s\'exécuter normalement', function () {
    $token = bin2hex(random_bytes(16));
    $file = deployProdOneshotTemplateForTest($token);

    try {
        $result = runProdOneshotTemplateForTest($file, [
            't' => $token,
            'cmd' => 'news:brief',
            'args' => json_encode(['article' => '999999937'], JSON_UNESCAPED_UNICODE),
        ]);

        // Atteint réellement NewsBriefCommand (lecture seule) - la réponse vient de la commande
        // Artisan elle-même (ou d'une exception PENDANT son exécution, ex. connexion DB propre au
        // sous-processus de test), jamais d'un refus des gardes AVANT Laravel : ni jeton, ni
        // commande, ni argument. Ne suppose pas QUEL backend DB répond ici (non pertinent pour ce
        // test, qui vérifie la liste blanche - pas NewsBriefCommand) : `[EXIT_CODE]`/`ERREUR :`
        // prouvent tous deux que le kernel a bien été atteint.
        expect($result['stdout'])
            ->not->toContain('jeton invalide')
            ->not->toContain('commande hors liste blanche')
            ->not->toContain('argument hors liste blanche')
            ->not->toContain('expiré')
            ->toMatch('/Fiche introuvable|\[EXIT_CODE\]|ERREUR :/');
    } finally {
        @unlink($file);
    }
});

test('un jeton invalide reste refusé (non-régression)', function () {
    $token = bin2hex(random_bytes(16));
    $file = deployProdOneshotTemplateForTest($token);

    try {
        $result = runProdOneshotTemplateForTest($file, [
            't' => 'mauvais-jeton',
            'cmd' => 'news:brief',
            'args' => json_encode(['article' => '999999937'], JSON_UNESCAPED_UNICODE),
        ]);

        expect($result['stdout'])->toContain('jeton invalide ou absent');
        expect($result['exitCode'])->toBe(1);
    } finally {
        @unlink($file);
    }
});

test('une commande hors COMMANDES_AUTORISEES reste refusée (non-régression)', function () {
    $token = bin2hex(random_bytes(16));
    $file = deployProdOneshotTemplateForTest($token);

    try {
        $result = runProdOneshotTemplateForTest($file, [
            't' => $token,
            'cmd' => 'news:regenerate-fallback-images',
            'args' => json_encode(['--force' => true], JSON_UNESCAPED_UNICODE),
        ]);

        expect($result['stdout'])->toContain('commande hors liste blanche');
        expect($result['exitCode'])->toBe(1);
    } finally {
        @unlink($file);
    }
});
