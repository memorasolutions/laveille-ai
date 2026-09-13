<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Console\Concerns\SelectsArticlesPendingAutoDetection;

/**
 * Backfill BORNÉ (source=auto) des fiches de glossaire pour les actualités publiées AVANT
 * l'écriture automatique intégrée à NewsToolSyncAction::suggest() (ticket #2524, 2026-09-13).
 * Jumelle exacte de BackfillAutoToolDetectionCommand (news:backfill-auto-tools) - même forme,
 * mêmes options, même prudence - pour le pivot news_article_term plutôt que news_article_tool.
 *
 * Différence structurelle avec le pendant outils : suggest() ÉCRIT DÉJÀ les termes détectés
 * comme effet de bord (quand $persistAutoTerms vaut true) - cette commande n'a donc PAS besoin
 * d'un second appel d'attache équivalent à attachAuto() : suggest() suffit. En mode --dry-run,
 * $persistAutoTerms est mis à false pour ne RIEN écrire, et la mesure passe par
 * NewsToolSyncAction::suggestGlossaryTermIds() - jumelle en lecture seule de suggest(), qui
 * partage la même résolution (resolveAutoGlossaryTermIds()) sans jamais écrire.
 *
 * Volontairement une commande manuelle, PAS une migration de déploiement - même doctrine que
 * BackfillAutoToolDetectionCommand (incident 2026-07-10, migration de rattrapage ayant bloqué
 * la CI plus de 10 minutes sur un jeu de données prod trop volumineux pour un traitement
 * synchrone non borné). `--limit` par défaut à 200 garantit une exécution rapide ; relancer
 * plusieurs fois pour rattraper tout le retard (idempotent, sans doublon - le pivot porte une
 * contrainte unique (news_article_id, term_id) et l'attache interne de suggest() ne touche
 * jamais une liaison déjà existante).
 *
 * CORRECTIF ticket #2525 (2026-09-13) : la sélection du lot ne repose plus sur
 * whereDoesntHave('terms') (confond « aucune liaison » et « jamais examinée » - une fiche sans
 * AUCUNE mention réelle de glossaire, absence normale, restait éternellement dans ce périmètre
 * et se faisait retraiter à chaque exécution). Elle s'appuie désormais sur la colonne horodatée
 * `terms_examined_at` (voir migration 2026_09_13_010000 et
 * Modules\News\Console\Concerns\SelectsArticlesPendingAutoDetection, partagée avec
 * BackfillAutoToolDetectionCommand - même défaut corrigé une seule fois aux deux endroits).
 * CHAQUE fiche traitée reçoit désormais son horodatage d'examen, qu'elle ait ou non produit une
 * liaison : une fiche examinée sans correspondance ne revient plus jamais dans le lot, sauf
 * demande explicite de réexamen complet (--rescanner).
 */
class BackfillAutoTermDetectionCommand extends Command
{
    use SelectsArticlesPendingAutoDetection;

    private const EXAMINED_COLUMN = 'terms_examined_at';

    protected $signature = 'news:backfill-auto-terms {--limit=200 : Nombre maximal d\'actualités traitées par exécution} {--dry-run : Mesurer sans rien écrire ni purger} {--echantillon : Tirer les fiches AU HASARD au lieu des plus anciennes, pour une mesure representative (simulation seulement)} {--rescanner : Ignorer l\'horodatage d\'examen et repasser sur tout le corpus déjà examiné (nécessaire après une amélioration du détecteur)}';

    protected $description = 'Détecte et lie automatiquement (source=auto) les fiches de glossaire pour les actualités publiées jamais examinées';

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
            $this->info('Aucune actualité publiée en attente d\'examen pour le glossaire - rien à faire.');

            return self::SUCCESS;
        }

        $processed = 0;
        $totalAttached = 0;
        $reparables = 0;

        foreach ($articles as $article) {
            // ACTION : ticket #2524 (2026-09-13) - suggest() écrit LUI-MÊME (source=auto) les
            // termes détectés dans news_article_term comme effet de bord (persistAutoTerms=true
            // par défaut) : aucun second appel d'attache équivalent à attachAuto() n'est
            // nécessaire ici, contrairement au pendant outils (voir docblock de classe
            // ci-dessus). En --dry-run, on appelle plutôt suggestGlossaryTermIds() - jumelle en
            // LECTURE SEULE de suggest(), qui partage la même résolution sans jamais écrire - et
            // le compte de la Collection retournée tient lieu de mesure « réparable ».
            //
            // ACTION : ticket #2525 - la sélection n'exclut plus les fiches déjà liées (voir
            // trait partagé), donc une fiche peut arriver ici avec des termes DÉJÀ attachés
            // (détection live à la publication, ou passage précédent avec --rescanner). Compter
            // AVANT/APRÈS (delta) plutôt que le total courant de terms() : sans ce garde, une
            // fiche déjà pourvue mais sans nouvelle mention se ferait compter à tort comme
            // « réparable » et déclencherait une purge de cache inutile.
            // MCP: SELF (<5 lignes)
            if ($dryRun) {
                $count = $action->suggestGlossaryTermIds($article)->count();
                $aDesCorrespondances = $count > 0;
            } else {
                $avant = $article->terms()->count();
                $action->suggest($article);
                $apres = $article->terms()->count();
                $count = $apres - $avant;
                // ACTION : ticket #2525 - ne JAMAIS déduire « aucune correspondance » d'un delta
                // nul. Une fiche déjà liée lors d'un passage précédent en a évidemment, et son
                // delta vaut pourtant zéro : mesuré le 2026-09-13, un lot de 400 a annoncé
                // « 400 n'avaient aucune correspondance » alors que 323 d'entre elles portaient
                // déjà des liaisons. Le delta mesure ce que CE passage a ajouté ; le total, lui,
                // mesure si la fiche a des correspondances.
                // MCP: SELF (<5 lignes)
                $aDesCorrespondances = $apres > 0;
            }

            if ($aDesCorrespondances) {
                $reparables++;
            }

            if ($count > 0) {

                if ($dryRun) {
                    $this->line("  [simulation] article #{$article->id} : {$count} terme(s) de glossaire seraient liés");
                } else {
                    $totalAttached += $count;

                    // ACTION: purger le cache public de la fiche juste après un rattachement réel.
                    // MCP: SELF (<5 lignes)
                    // RAISON: les routes publiques portent cacheResponse:600 - sans cette purge, la
                    // page continue d'être servie telle qu'elle était pendant 10 minutes, et une
                    // vérification faite dans la foulée conclut à tort que la commande n'a rien fait.
                    NewsToolSyncAction::invalidatePublicCache($article);

                    $this->line("  article #{$article->id} : {$count} terme(s) de glossaire");
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
            // Deux populations à ne pas confondre : une fiche sans terme lié n'est pas
            // forcément un défaut. Une actualité sur une politique publique n'a aucune raison
            // de mentionner une fiche du glossaire ; seules les fiches pour lesquelles
            // suggestGlossaryTermIds() propose quelque chose sont réellement réparables.
            $sansSuggestion = $processed - $reparables;
            $nature = $echantillon ? 'tirées AU HASARD' : 'les plus anciennes par identifiant';
            $this->info("[simulation] {$processed} fiche(s) examinée(s), {$nature} : {$reparables} mentionnent réellement une fiche de glossaire (réparables), {$sansSuggestion} n'en mentionnent aucune (absence normale). Aucune écriture, aucune purge.");
            $this->comment("Fiches publiées jamais examinées pour le glossaire (retard réel du rattrapage) : {$remaining}.");
        } else {
            // ACTION : ticket #2525 - message honnête qui distingue les QUATRE nombres :
            // examinées, avec au moins une liaison, sans aucune correspondance, et restantes à
            // EXAMINER (jamais « restantes sans terme », qui confondrait à nouveau les deux
            // populations que ce ticket sépare).
            // MCP: SELF (<5 lignes)
            $sansCorrespondance = $processed - $reparables;
            $this->info("{$processed} actualité(s) examinée(s), {$reparables} ont au moins un terme de glossaire, dont {$totalAttached} liaison(s) NOUVELLE(S) posée(s) par ce passage, {$sansCorrespondance} n'ont aucune correspondance. {$remaining} fiche(s) restent à examiner.");

            if ($remaining > 0) {
                $this->comment('Relancer la commande pour continuer le rattrapage.');
            }
        }

        return self::SUCCESS;
    }
}
