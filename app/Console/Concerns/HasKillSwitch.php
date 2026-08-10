<?php

declare(strict_types=1);

namespace App\Console\Concerns;

/**
 * Trait permettant à une commande Artisan de vérifier un kill switch Pennant
 * avant exécution. Portable : fonctionne même si Pennant n'est pas installé.
 */
trait HasKillSwitch
{
    /**
     * Vérifie si la commande doit être ignorée à cause d'un kill switch.
     *
     * Retourne true (et affiche un warn) si :
     *  - Pennant est installé
     *  - Le flag $flag est désactivé (Feature::active retourne false)
     *  - L'option --force n'est pas passée
     *
     * Retourne false sinon (exécution normale).
     */
    public function shouldSkipForKillSwitch(string $flag): bool
    {
        if (! class_exists(\Laravel\Pennant\Feature::class)) {
            return false;
        }

        if (\Laravel\Pennant\Feature::active($flag)) {
            return false;
        }

        if ($this->hasOption('force') && $this->option('force')) {
            return false;
        }

        $this->components->warn("Kill switch {$flag} actif.");

        return true;
    }
}
