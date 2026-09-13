<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Console\Concerns\SelectsArticlesPendingAutoDetection;

/**
 * Backfill BORNÉ (source=auto) des outils annuaire pour les actualités publiées AVANT
 * l'auto-détection à la publication (AutoDetectNewsToolsJob + NewsArticleObserver).
 *
 * Volontairement une commande manuelle, PAS une migration de déploiement : une première
 * tentative en migration a bloqué le pipeline CI plus de 10 minutes sur un jeu de données
 * prod trop volumineux pour un traitement synchrone non borné (incident 2026-07-10, run
 * annulé, migration retirée avant réplication en base grâce au wrapping transactionnel).
 * `--limit` par défaut à 200 garantit une exécution rapide ; relancer plusieurs fois pour
 * rattraper tout le retard (idempotent, sans doublon - attachAuto() ne touche jamais une
 * liaison déjà existante).
 *
 * CORRECTIF ticket #2525 (2026-09-13) : la sélection du lot ne repose plus sur
 * whereDoesntHave('tools') (confond « aucune liaison » et « jamais examinée » - une fiche sans
 * AUCUNE mention réelle d'outil, absence normale, restait éternellement dans ce périmètre et se
 * faisait retraiter à chaque exécution ; mesuré en production sur cinq lots consécutifs : 400
 * fiches traitées par lot, mais seulement 303 puis 249 puis 189 puis 149 réellement évacuées).
 * Elle s'appuie désormais sur la colonne horodatée `tools_examined_at` (voir migration
 * 2026_09_13_010000 et Modules\News\Console\Concerns\SelectsArticlesPendingAutoDetection,
 * partagée avec BackfillAutoTermDetectionCommand - même défaut corrigé une seule fois aux deux
 * endroits). CHAQUE fiche traitée reçoit désormais son horodatage d'examen, qu'elle ait ou non
 * produit une liaison : une fiche examinée sans correspondance ne revient plus jamais dans le
 * lot, sauf demande explicite de réexamen complet (--rescanner).
 */
class BackfillAutoToolDetectionCommand extends Command
{
    use SelectsArticlesPendingAutoDetection;

    private const EXAMINED_COLUMN = 'tools_examined_at';

    protected $signature = 'news:backfill-auto-tools {--limit=200 : Nombre maximal d\'actualités traitées par exécution} {--dry-run : Mesurer sans rien écrire ni purger} {--echantillon : Tirer les fiches AU HASARD au lieu des plus anciennes, pour une mesure representative (simulation seulement)} {--rescanner : Ignorer l\'horodatage d\'examen et repasser sur tout le corpus déjà examiné (nécessaire après une amélioration du détecteur)}';

    protected $description = 'Détecte et lie automatiquement (source=auto) les outils annuaire pour les actualités publiées jamais examinées';

    public function handle(NewsToolSyncAction $action): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $rescanner = (bool) $this->option('rescanner');
        // ACTION : tirage AU HASARD, réservé à la simulation.
        // MCP: SELF (<5 lignes)
        // RAISON: en simulation rien n'est écrit, donc deux appels successifs renvoient
        // exactement les MÊMES premières fiches par identifiant - c'est-à-dire les PLUS
        // ANCIENNES. Mesurer un taux sur cet échantillon, c'est mesurer le passé et le
        // présenter comme le tout. Le tirage aléatoire donne une proportion représentative
        // en un seul appel, ce qui compte quand une exécution complète dépasse la limite
        // de temps du serveur. Interdit hors simulation : sur un vrai rattrapage, un ordre
        // aléatoire empêche de reprendre là où l'on s'était arrêté.
        $echantillon = $dryRun && (bool) $this->option('echantillon');

        $articles = $this->selectArticlesPendingExamination(self::EXAMINED_COLUMN, $limit, $rescanner, $echantillon);

        if ($articles->isEmpty()) {
            $this->info('Aucune actualité publiée en attente d\'examen pour les outils - rien à faire.');

            return self::SUCCESS;
        }

        $processed = 0;
        $totalAttached = 0;
        $reparables = 0;

        foreach ($articles as $article) {
            // ACTION : ticket #2524 (2026-09-13) - suggest() écrit désormais aussi (source=auto)
            // les fiches de glossaire détectées dans news_article_term, en PARALLÈLE du présent
            // rattrapage des outils (comportement des outils lui-même strictement inchangé).
            // persistAutoTerms:false en --dry-run PRÉSERVE le contrat "Aucune écriture, aucune
            // purge" affiché plus bas par cette même commande - sans ce garde, la simulation
            // écrirait quand même dans news_article_term, rendant ce message faux.
            // MCP: SELF (<5 lignes)
            $suggested = $action->suggest($article, persistAutoTerms: ! $dryRun);

            if ($suggested->isNotEmpty()) {
                $reparables++;

                if ($dryRun) {
                    $this->line("  [simulation] article #{$article->id} : {$suggested->count()} outil(s) seraient liés");
                } else {
                    $count = $action->attachAuto($article, $suggested);
                    $totalAttached += $count;

                    // ACTION: purger le cache public de la fiche juste après un rattachement réel.
                    // MCP: SELF (<5 lignes)
                    // RAISON: les routes publiques portent cacheResponse:600 - sans cette purge, la
                    // page continue d'être servie telle qu'elle était pendant 10 minutes, et une
                    // vérification faite dans la foulée conclut à tort que la commande n'a rien fait.
                    NewsToolSyncAction::invalidatePublicCache($article);

                    $this->line("  article #{$article->id} : {$count} outil(s)");
                }
            }

            // ACTION : coeur du correctif ticket #2525 - marquer la fiche EXAMINÉE, qu'elle ait
            // ou non produit une correspondance, pour qu'elle ne revienne plus jamais dans le
            // lot d'un rattrapage normal. Jamais en --dry-run : le mode simulation promet
            // « aucune écriture, aucune purge ».
            // MCP: SELF (<5 lignes)
            if (! $dryRun) {
                $this->markArticleExamined($article, self::EXAMINED_COLUMN);
            }

            $processed++;
        }

        $remaining = $this->countArticlesPendingExamination(self::EXAMINED_COLUMN);

        if ($dryRun) {
            // Deux populations à ne pas confondre : une fiche sans outil lié n'est pas
            // forcément un défaut. Une actualité sur une politique publique n'a aucune raison
            // de mentionner un outil de l'annuaire ; seules les fiches pour lesquelles
            // suggest() propose quelque chose sont réellement réparables.
            $sansSuggestion = $processed - $reparables;
            $nature = $echantillon ? 'tirées AU HASARD' : 'les plus anciennes par identifiant';
            $this->info("[simulation] {$processed} fiche(s) examinée(s), {$nature} : {$reparables} mentionnent réellement un outil de l'annuaire (réparables), {$sansSuggestion} n'en mentionnent aucun (absence normale). Aucune écriture, aucune purge.");
            $this->comment("Fiches publiées jamais examinées pour les outils (retard réel du rattrapage) : {$remaining}.");
        } else {
            // ACTION : ticket #2525 - message honnête qui distingue les QUATRE nombres :
            // examinées, avec au moins une liaison, sans aucune correspondance, et restantes à
            // EXAMINER (jamais « restantes sans outil », qui confondrait à nouveau les deux
            // populations que ce ticket sépare).
            // MCP: SELF (<5 lignes)
            $sansCorrespondance = $processed - $reparables;
            $this->info("{$processed} actualité(s) examinée(s), {$reparables} ont reçu au moins un outil ({$totalAttached} outil(s) auto-lié(s) au total), {$sansCorrespondance} n'avaient aucune correspondance. {$remaining} fiche(s) restent à examiner.");

            if ($remaining > 0) {
                $this->comment('Relancer la commande pour continuer le rattrapage.');
            }
        }

        return self::SUCCESS;
    }
}
