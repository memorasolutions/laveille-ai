<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Services\ContentExtractor;

/**
 * Commande interne utilisee exclusivement par ContentExtractor::extract() (branche isolee) -
 * jamais invoquee directement par un cron ni par un humain.
 *
 * ACTION : isolation memoire (2026-08-31, ticket #2110) - un premier garde-fou de taille
 * brute sur le HTML avant Readability n'a pas empeche une nouvelle exhaustion memoire
 * mesuree en production le jour meme de son deploiement, sur la MEME pile d'appel
 * (Masterminds\HTML5, vendor/masterminds/html5/src/HTML5/Parser/Scanner.php:351) pour un
 * document pourtant sous le plafond de taille - preuve que la taille brute ne suffit pas a
 * predire l'amplification memoire du parsing. Cette commande execute le corps reel de
 * l'extraction (ContentExtractor::extractInProcess()) dans un processus PHP DEDIE et
 * JETABLE : si ce processus est tue par epuisement memoire, seul lui meurt - le cron
 * news:fetch parent, qui boucle sur des dizaines de sources, continue intact.
 * MCP: SELF (<5 lignes utiles, commande fine autour d'un service existant)
 * RAISON: isoler ce qu'on ne peut pas borner a coup sur, plutot que de deviner un nouveau
 * plafond de taille qui pourrait echouer une deuxieme fois.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class ExtractIsolatedCommand extends Command
{
    protected $signature = 'news:extract-isolated {url}';

    protected $description = 'Usage interne uniquement - extrait le contenu d\'une URL dans un processus isole (jamais planifie directement).';

    public function handle(ContentExtractor $extractor): int
    {
        $result = $extractor->extractInProcess((string) $this->argument('url'));

        $this->line(json_encode($result));

        return self::SUCCESS;
    }
}
