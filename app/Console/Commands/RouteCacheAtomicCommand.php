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
 * absent, pas meme un instant.
 *
 * Corrige une premiere version de cette meme commande (revue independante, 2026-08-31)
 * qui deplacait le fichier reel avant reconstruction et recreait ainsi, sans le vouloir,
 * le defaut meme qu'elle pretendait corriger.
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
            if ($previousEnv === false) {
                putenv('APP_ROUTES_CACHE');
            } else {
                putenv("APP_ROUTES_CACHE={$previousEnv}");
            }

            // Defensif seulement : le leurre n'est jamais cense recevoir d'ecriture.
            if (file_exists($decoy)) {
                @unlink($decoy);
            }
        }
    }
}
