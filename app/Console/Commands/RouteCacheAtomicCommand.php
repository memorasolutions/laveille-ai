<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Foundation\Console\RouteCacheCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Throwable;

/**
 * Cette commande corrige un bug de production dans le cache des routes natif,
 * qui supprimait le fichier de cache avant de le regenerer, creant une fenetre
 * ou toute requete pouvait echouer faute de fichier. Cette version realise
 * une bascule atomique du cache sans jamais laisser le fichier cible absent.
 *
 * Utiliser cette commande dans les pipelines de deploiement a la place de `route:cache`.
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
    protected $description = 'Creer un cache atomique des routes sans fenetre d\'indisponibilite';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $target = $this->laravel->getCachedRoutesPath();
        $hadExisting = $this->files->exists($target);

        if ($hadExisting) {
            $backup = $target . '.ancien-' . getmypid();

            try {
                if (! rename($target, $backup)) {
                    $this->components->error('Impossible de sauvegarder le fichier de cache existant.');

                    return Command::FAILURE;
                }
            } catch (Throwable $e) {
                $this->components->error('Erreur lors de la sauvegarde du fichier de cache : ' . $e->getMessage());

                return Command::FAILURE;
            }
        }

        try {
            $routes = $this->getFreshApplicationRoutes();

            if (count($routes) === 0) {
                // Restaurer l'ancien cache si disponible
                if ($hadExisting && isset($backup) && $this->files->exists($backup) && ! $this->files->exists($target)) {
                    rename($backup, $target);
                }

                $this->components->error("Your application doesn't have any routes.");

                return Command::SUCCESS;
            }

            foreach ($routes as $route) {
                $route->prepareForSerialization();
            }

            $this->files->replace($target, $this->buildRouteCacheFile($routes));

            if ($hadExisting && isset($backup)) {
                $this->files->delete($backup);
            }

            $this->components->info('Fichier de cache des routes bascule de facon atomique.');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            // Restauration en cas d'echec
            if ($hadExisting && isset($backup) && ! $this->files->exists($target) && $this->files->exists($backup)) {
                rename($backup, $target);
            }

            throw $e;
        }
    }
}
