<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Remplace la commande native `config:cache` de Laravel pour l'empêcher de s'exécuter sur
 * ce projet - elle est FORMELLEMENT INTERDITE (incident du 2026-06-30 : une fois la
 * configuration mise en cache, tout env() hors des fichiers config/ retourne null à
 * l'exécution, ce qui avait refermé le module Académie en production).
 *
 * Mécanisme : l'attribut #[AsCommand(name: 'config:cache')] ci-dessous porte le même nom
 * que la commande native Illuminate\Foundation\Console\ConfigCacheCommand. Les commandes
 * chargées depuis app/Console/Commands sont résolues après les commandes natives du coeur
 * (Illuminate\Console\Application::resolveCommands() s'exécute après le démarrage qui
 * enregistre les commandes natives) : cette classe remplace donc l'entrée native dans la
 * table des commandes, pour tout chemin d'invocation qui passe par le nom "config:cache" -
 * un `php artisan config:cache` tapé directement, mais aussi tout appel composé qui
 * l'invoque en interne, tel `php artisan optimize` (Illuminate\Foundation\Console\
 * OptimizeCommand), dont la toute première sous-tâche est justement `config:cache`.
 *
 * Le composant Illuminate\Console\View\Components\Task (utilisé par OptimizeCommand pour
 * chaque sous-tâche) attrape l'exception levée ci-dessous, affiche l'état FAIL, puis la
 * relance : `php artisan optimize` échoue donc lui aussi entièrement, avec un code de
 * sortie non nul, plutôt que de continuer en pensant avoir réussi alors que la mise en
 * cache de la configuration n'a jamais eu lieu.
 *
 * Protection complémentaire, au niveau des fichiers texte plutôt que du framework : voir
 * tests/Architecture/ConfigCacheForbiddenTest.php (elle attrape le cas où la commande est
 * écrite dans un script hors application, que cette classe ne peut pas voir). Contexte
 * complet : docs/CONTRAINTES-SOUS-AGENTS.md.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 */
#[AsCommand(name: 'config:cache')]
class ConfigCacheGuardCommand extends Command
{
    /**
     * Nom de la commande Artisan (identique à la commande native qu'elle remplace).
     *
     * @var string
     */
    protected $name = 'config:cache';

    /**
     * Description affichée dans `php artisan list`.
     *
     * @var string
     */
    protected $description = "INTERDITE sur ce projet : figerait la configuration et casserait env() à l'exécution (incident 2026-06-30).";

    /**
     * Bloque systématiquement l'exécution. Ne retourne jamais : lève toujours une
     * exception, y compris lorsque cette commande est invoquée silencieusement par un
     * appelant composé comme `php artisan optimize` (voir docblock de classe) - le message
     * de l'exception reste rendu clairement par Laravel au niveau supérieur, même quand la
     * sortie de cet appel précis est réduite au silence par l'appelant.
     *
     * @throws RuntimeException toujours.
     */
    public function handle(): never
    {
        $message = 'La commande config:cache est interdite sur ce projet : elle fige la '
            .'configuration et rend env() indisponible en dehors des fichiers config/ à '
            ."l'exécution, ce qui a déjà fermé le module Académie en production (incident "
            .'du 2026-06-30). Utilisez plutôt route:cache-atomic, event:cache et '
            .'view:cache : aucune des trois ne dépend de env(). Voir '
            .'docs/CONTRAINTES-SOUS-AGENTS.md pour le détail.';

        $this->components->error($message);

        throw new RuntimeException($message);
    }
}
