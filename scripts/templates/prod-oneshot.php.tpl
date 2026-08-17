<?php

declare(strict_types=1);

/**
 * Script PHP autonome ONE-SHOT, généré par scripts/prod-artisan.sh - JAMAIS commité, JAMAIS
 * déployé par le pipeline CI (le rsync de déploiement exclut déjà `/public/_*.php`). Contourne
 * l'indisponibilité du terminal cPanel et du gestionnaire de fichiers, et le mutisme de
 * `tinker` via SSH sur ce compte (docs/CONTRAINTES-SOUS-AGENTS.md, section 5).
 *
 * Amorce Laravel, exécute EXACTEMENT la ligne de commande donnée (identique à un `php artisan
 * ...` tapé en SSH), écrit sa sortie telle quelle dans la réponse HTTP, PUIS SE SUPPRIME
 * LUI-MÊME (et ses éventuels fichiers d'accompagnement --payload/--image) - une seconde requête
 * sur la même URL doit répondre 404. Protégé par un jeton à usage : refuse toute exécution sans
 * le jeton exact fourni en paramètre GET ?token=.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

$expectedToken = '__TOKEN__';
$providedToken = $_GET['token'] ?? null;

if (! is_string($providedToken) || $providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo "jeton invalide ou absent\n";

    exit(1);
}

// ACTION : auto-suppression garantie même si l'exécution plus bas lève une exception - le
// shutdown handler tourne dans tous les cas. $cleanupPaths est complété plus bas (fichiers
// --payload/--image), TOUJOURS par référence, AVANT que le script ne se termine.
// MCP: SELF (<5 lignes)
// RAISON: garde-fou explicite du mandat, "tout cron/script temporaire retiré immédiatement".
$cleanupPaths = [__FILE__];

register_shutdown_function(static function () use (&$cleanupPaths): void {
    foreach ($cleanupPaths as $path) {
        if (is_string($path) && $path !== '' && is_file($path)) {
            @unlink($path);
        }
    }
});

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// La ligne ci-dessous est la ligne de commande EXACTE (mêmes jetons que si elle avait été tapée
// après `php artisan` en SSH, déjà correctement quotée par le générateur bash). {{STORAGE}} ne
// peut être résolu qu'ICI, une fois Laravel amorcé (storage_path() n'existe pas avant).
$commandLine = '__ARTISAN_CALL__';
$commandLine = str_replace('{{STORAGE}}', storage_path('app/oneshot-uploads'), $commandLine);

// Lignes générées par scripts/prod-artisan.sh ci-dessous, ajoutant au nettoyage les fichiers
// --payload/--image déposés dans storage/app/oneshot-uploads/ pour cette exécution (absentes si
// la commande n'en a reçu aucun - le générateur laisse alors cette zone vide).
__CLEANUP_MARKER__

header('Content-Type: text/plain; charset=utf-8');

try {
    $input = new \Symfony\Component\Console\Input\StringInput($commandLine);
    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $exitCode = $kernel->handle($input, $output);
    echo $output->fetch();
    if ($exitCode !== 0) {
        echo "\n[EXIT_CODE] {$exitCode}\n";
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'ERREUR : '.$e->getMessage()."\n";
}
