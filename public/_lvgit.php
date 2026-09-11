<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca>
 *
 * #250 — Endpoint git-pull token-protégé pour resync prod avec origin/master.
 * Évite les multiples cpanel_file_write par déploiement quand Shell API cPanel
 * est désactivée. Usage : curl -H "X-Lv-Git-Token: $LV_GIT_TOKEN" "https://laveille.ai/_lvgit.php"
 * Restrictions : token .env LV_GIT_TOKEN (64-char hex), commandes git
 * allowlist uniquement (fetch + reset --hard + log).
 *
 * 2026-09-10 - Durcissement (mandat sécurité du 2026-08-25, posé 16 jours après) :
 * (1) le jeton ne voyage plus dans la chaîne de requête (fuite via journaux d'accès, journaux
 *     des intermédiaires réseau, et en-tête de provenance) - il voyage désormais UNIQUEMENT dans
 *     l'en-tête X-Lv-Git-Token. L'ancienne forme ?t= est RETIRÉE sans compatibilité conservée
 *     (une compatibilité « au cas où » laisserait l'ancienne voie ouverte et annulerait le
 *     correctif). (2) l'option &seed=ClassName est retirée de l'allowlist : une semence de base
 *     de données peut réécrire des données de production derrière un seul jeton, ce qui n'a rien
 *     à faire dans un filet de déploiement d'urgence. Preuve comportementale :
 *     tests/Feature/LvGitEndpointSecurityTest.php.
 */

// Lit le token depuis .env sans booter Laravel (faster + no autoload required).
$envPath = __DIR__.'/../.env';
$envContent = @file_get_contents($envPath);
if ($envContent === false) {
    http_response_code(500);
    exit('env unreadable');
}

if (!preg_match('/^LV_GIT_TOKEN=(.+)$/m', $envContent, $matches)) {
    http_response_code(500);
    exit('token not configured');
}
$expectedToken = trim($matches[1]);

// Le jeton voyage UNIQUEMENT par en-tête de requête HTTP - jamais par la chaîne de requête
// (une adresse s'inscrit en clair dans les journaux d'accès, les journaux des intermédiaires
// réseau, et peut fuiter par l'en-tête Referer). Aucune lecture de repli sur $_GET['t'] : la
// conserver « au cas où » laisserait la voie non sécurisée ouverte et annulerait le correctif.
$providedToken = (string)($_SERVER['HTTP_X_LV_GIT_TOKEN'] ?? '');
if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

chdir(__DIR__.'/..');

$ssDir = __DIR__.'/screenshots';
$ssBackup = __DIR__.'/../storage/app/lvgit-ss-backup';

$commands = [
    ['/usr/bin/git', 'fetch', '--quiet', 'origin'],
];

// S133 anti-écrasement screenshots : la version prod prime sur le dépôt
// (le reset --hard ci-dessous écraserait sinon les screenshots uploadés en prod).
if (is_dir($ssDir)) {
    $commands[] = ['/bin/rm', '-rf', $ssBackup];
    $commands[] = ['/bin/mkdir', '-p', $ssBackup];
    $commands[] = ['/bin/cp', '-a', $ssDir.'/.', $ssBackup.'/'];
}

$commands[] = ['/usr/bin/git', 'reset', '--hard', 'origin/master'];

if (is_dir($ssDir)) {
    $commands[] = ['/bin/cp', '-a', $ssBackup.'/.', $ssDir.'/'];
}

$commands[] = ['/usr/bin/git', 'log', '-1', '--oneline'];
$commands[] = ['/usr/bin/git', 'status', '-s'];

// 2026-05-27 #311 : option `&cache=1` pour rafraîchir les caches Laravel après pull,
// bullet-proof quand cPanel UAPI est down (Shell API désactivée + File Manager API
// en restart). Workaround validé en S128 incident cPanel maintenance prolongée.
// Allowlist commandes artisan (view:clear + route:cache-atomic + event:cache + view:cache).
// 2026-05-27 #313 : ajout option `&migrate=1` (migration prod).
// 2026-09-10 : option `&seed=ClassName` RETIRÉE de l'allowlist (mandat sécurité du 2026-08-25) -
// une semence de base de données peut réécrire des données de production derrière un seul jeton,
// ce qui n'a rien à faire dans un filet de déploiement de secours. Voir commentaire d'en-tête.
$phpBin = null;
$resolvePhpBin = function () use (&$phpBin) {
    if ($phpBin !== null) {
        return $phpBin;
    }
    $bin = '/opt/cpanel/ea-php84/root/usr/bin/php';
    if (! is_executable($bin)) {
        $bin = trim((string) shell_exec('which php')) ?: '/usr/bin/php';
    }
    return $phpBin = $bin;
};
// 2026-08-23 : `config:cache` RETIRÉ de cette liste. Il y figurait alors que c'est la seule
// commande formellement interdite sur ce projet - elle a silencieusement REFERMÉ l'Académie en
// production (le middleware AcademyUnderConstruction ne lisait plus ACADEMY_UNDER_CONSTRUCTION
// du .env : tout env() devient null une fois la config mise en cache). La CI l'exclut depuis
// (.github/workflows/deploy.yml, étape « Build caches prod »), et docs/CONTRAINTES-SOUS-AGENTS.md
// l'interdit - mais ce script de déploiement de SECOURS, lui, l'exécutait encore. Or c'est
// précisément la voie qu'on emprunte quand la CI est indisponible, donc au pire moment.
// Liste alignée sur celle de la CI : route + event + view, aucune ne dépend d'env().
// 2026-08-31 #2096 : `route:cache` REMPLACÉ par `route:cache-atomic` (app/Console/Commands/
// RouteCacheAtomicCommand.php). La commande native supprimait le fichier de cache avant de
// le reconstruire - fenêtre où une requête ou une tâche planifiée démarrant l'application
// pouvait essuyer une erreur fatale. La version atomique ne touche jamais le fichier réel
// avant la bascule finale (rename() atomique). Précisément la voie de secours qui n'a PAS
// la protection du mode maintenance du pipeline CI - encore plus exposée que lui.
if (! empty($_GET['cache'])) {
    $phpBin = $resolvePhpBin();
    $commands[] = [$phpBin, 'artisan', 'view:clear'];
    $commands[] = [$phpBin, 'artisan', 'route:cache-atomic'];
    $commands[] = [$phpBin, 'artisan', 'event:cache'];
    $commands[] = [$phpBin, 'artisan', 'view:cache'];
}
if (! empty($_GET['migrate'])) {
    $phpBin = $resolvePhpBin();
    $commands[] = [$phpBin, 'artisan', 'migrate', '--force'];
}
foreach ($commands as $cmd) {
    echo '$ '.implode(' ', $cmd)."\n";
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (is_resource($proc)) {
        echo stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        if ($stderr !== '') {
            echo "stderr: ".$stderr;
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
    } else {
        echo "[proc_open failed]\n";
    }
    echo "\n";
}

// 2026-05-27 #311 : si cache refresh demandé, écrire _lvversion.txt avec semver courant
// (footer prod synchronisé sans cPanel UAPI File Manager).
if (! empty($_GET['cache'])) {
    $cfgPath = __DIR__.'/../config/version.php';
    if (file_exists($cfgPath)) {
        $cfg = include $cfgPath;
        $ver = $cfg['semver'] ?? 'unknown';
        @file_put_contents(__DIR__.'/_lvversion.txt', $ver."\n");
        echo "[_lvversion.txt] $ver\n";
    }
}

echo "OK ".date('c')."\n";
