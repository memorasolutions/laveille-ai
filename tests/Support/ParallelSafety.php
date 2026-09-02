<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Tests\Support;

/**
 * Détection du mode parallèle, pour les tests qui mutent un fichier PARTAGÉ du dépôt.
 *
 * POURQUOI CETTE CLASSE EXISTE (mesuré le 2026-09-02, ticket #2179)
 * -----------------------------------------------------------------
 * Trois fichiers de test écrivent dans `base_path('modules_statuses.json')`, le VRAI fichier
 * du dépôt, pas une copie. Sauvegarder puis restaurer ne protège que le processus qui le
 * fait : un AUTRE processus qui boote pendant la fenêtre de mutation lit un instantané
 * tronqué et échoue, avec un message qui varie selon la clé absente à cet instant précis
 * (« No hint path defined for [fronttheme] », « Module [AI] requires [Settings] »...).
 * D'où des échecs qui semblent aléatoires et sans rapport entre eux.
 *
 * Course reproduite en faisant tourner deux de ces fichiers dans des processus concurrents :
 * échec après une dizaine d'itérations. Aucun verrou n'existe dans la chaîne Laravel
 * (`Illuminate\Filesystem\Filesystem::put()/get()` ont `$lock = false` par défaut), et
 * `FileActivator` ne lit le fichier qu'UNE seule fois, au démarrage de l'application.
 *
 * POURQUOI UNE CLASSE PLUTÔT QUE LE BLOC RECOPIÉ TROIS FOIS
 * ---------------------------------------------------------
 * La règle DRY du projet autorise la duplication de quelques lignes, SAUF quand elle encode
 * une règle de sécurité dont la divergence serait dangereuse. C'est exactement le cas ici :
 * si une copie oubliait une des quatre variables, son fichier redeviendrait dangereux en
 * silence, et le symptôme réapparaîtrait ailleurs sans qu'on fasse le lien.
 *
 * `Tests\` est déjà déclaré en PSR-4 sur `tests/` dans `autoload-dev` : cette classe est donc
 * autoloadée sans modifier `composer.json`, et reste absente d'une installation `--no-dev`.
 */
final class ParallelSafety
{
    /**
     * Le lanceur de tests tourne-t-il en mode parallèle ?
     *
     * Les quatre sources sont testées parce qu'aucune n'est fiable seule : `LARAVEL_PARALLEL_TESTING`
     * est posée par le mode parallèle natif de Laravel, `TEST_TOKEN` et `PARATEST` par paratest
     * selon la façon dont il est lancé. `env()` est doublé de `getenv()` car la configuration
     * peut être mise en cache avant que la variable ne soit posée.
     */
    public static function isParallel(): bool
    {
        return (bool) (env('LARAVEL_PARALLEL_TESTING')
            || getenv('LARAVEL_PARALLEL_TESTING')
            || getenv('TEST_TOKEN')
            || getenv('PARATEST'));
    }

    /**
     * Message d'ignorance, identique partout pour que le motif soit repérable dans un rapport.
     */
    public static function sharedFileSkipReason(string $fichier = 'modules_statuses.json'): string
    {
        return "Mute le fichier partagé {$fichier} - non sûr en parallèle (voir Tests\\Support\\ParallelSafety).";
    }
}
