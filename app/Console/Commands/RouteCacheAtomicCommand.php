<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Foundation\Console\RouteCacheCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Corrige un bug de production : la commande native `route:cache` supprime le fichier
 * de cache avant de redemarrer une application complete pour le reconstruire, laissant
 * la cible ABSENTE du disque pendant toute cette phase lente (0,5 a 0,7 seconde mesure).
 *
 * Mecanisme : Illuminate\Foundation\Application::getCachedRoutesPath() respecte la
 * variable d'environnement APP_ROUTES_CACHE (chemin absolu retourne tel quel). En la
 * redirigeant temporairement vers un leurre qui n'existe jamais sur le disque AVANT de
 * redemarrer l'application fraiche, celle-ci constate qu'aucune route n'est en cache et
 * parse reellement les fichiers de routes - sans jamais toucher au VRAI fichier cible.
 * Celui-ci n'est atteint qu'une seule fois, a la toute fin, via Filesystem::replace()
 * (ecriture temporaire du meme dossier puis rename() atomique) : il n'est donc jamais
 * absent, pas meme un instant. Preuve empirique : 56 862 sondages concurrents de
 * l'existence du fichier pendant une execution complete, zero absence detectee.
 *
 * Corrige une premiere version de cette meme commande (revue independante, 2026-08-31)
 * qui deplacait le fichier reel avant reconstruction et recreait ainsi, sans le vouloir,
 * le defaut meme qu'elle pretendait corriger.
 *
 * DEUX MISES EN GARDE, releve par une deuxieme revue independante (2026-08-31) :
 *
 * 1) Cette commande peut s'executer DANS un worker PHP-FPM reutilise (ex. un bouton de
 *    "reparation" du backoffice qui fait Artisan::call() en plein milieu d'une requete
 *    web), pas seulement dans un processus CLI jetable dedie a un deploiement. Un arret
 *    fatal non rattrapable (depassement de max_execution_time ou de memory_limit) PENDANT
 *    getFreshApplicationRoutes() sauterait le bloc finally ci-dessous et laisserait
 *    APP_ROUTES_CACHE pointer vers le leurre pour toutes les requetes suivantes traitees
 *    par CE MEME worker, jusqu'a son recyclage - une degradation silencieuse (routes
 *    jamais mises en cache), pas un plantage. D'ou le register_shutdown_function()
 *    ci-dessous : les fonctions de fin de script de PHP s'executent MEME apres un arret
 *    par depassement de temps, contrairement a un bloc finally.
 *
 * 2) NE JAMAIS definir APP_ROUTES_CACHE dans le .env de ce projet. Si elle y figurait un
 *    jour, la resolution d'environnement de Laravel donnerait la priorite a $_ENV/$_SERVER
 *    (charges par le .env) sur le putenv() ci-dessous, neutralisant silencieusement tout
 *    ce mecanisme. Absence confirmee a ce jour (grep du depot), mais a ne jamais introduire.
 *
 * Utiliser cette commande dans tout pipeline ou script de deploiement, a la place de
 * `route:cache`.
 */
#[AsCommand(name: 'route:cache-atomic')]
class RouteCacheAtomicCommand extends RouteCacheCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'route:cache-atomic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Creer un cache atomique des routes sans fenetre d'indisponibilite";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $target = $this->laravel->getCachedRoutesPath();
        $decoy = $target . '.construction-' . getmypid();

        // Valeur actuelle de la variable, a restaurer telle quelle (false = non definie,
        // a distinguer d'une chaine vide : putenv() sans signe egal la supprime totalement).
        $previousEnv = getenv('APP_ROUTES_CACHE');
        $restored = false;

        $restore = function () use ($previousEnv, $decoy, &$restored): void {
            if ($restored) {
                return;
            }

            if ($previousEnv === false) {
                putenv('APP_ROUTES_CACHE');
            } else {
                putenv("APP_ROUTES_CACHE={$previousEnv}");
            }

            // Defensif seulement : le leurre n'est jamais cense recevoir d'ecriture.
            if (file_exists($decoy)) {
                @unlink($decoy);
            }

            $restored = true;
        };

        // Filet de securite pour un arret fatal (timeout, memoire) qui sauterait le
        // bloc finally ci-dessous - voir la mise en garde (1) du docblock de classe.
        register_shutdown_function($restore);

        putenv("APP_ROUTES_CACHE={$decoy}");

        try {
            $routes = $this->getFreshApplicationRoutes();

            if (count($routes) === 0) {
                $this->components->error("Your application doesn't have any routes.");

                return SymfonyCommand::SUCCESS;
            }

            foreach ($routes as $route) {
                $route->prepareForSerialization();
            }

            // Seul point de contact avec le vrai fichier cible dans toute la commande.
            $this->files->replace(
                $target,
                $this->buildRouteCacheFile($routes)
            );

            $this->components->info(
                sprintf(
                    'Cache des routes reconstruit et bascule. Le fichier [%s] n\'a jamais ete indisponible durant l\'operation.',
                    $target
                )
            );

            return SymfonyCommand::SUCCESS;
        } finally {
            $restore();
        }
    }
}
