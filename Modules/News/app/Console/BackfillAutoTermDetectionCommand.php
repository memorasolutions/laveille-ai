<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;

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
 */
class BackfillAutoTermDetectionCommand extends Command
{
    protected $signature = 'news:backfill-auto-terms {--limit=200 : Nombre maximal d\'actualités traitées par exécution} {--dry-run : Mesurer sans rien écrire ni purger} {--echantillon : Tirer les fiches AU HASARD au lieu des plus anciennes, pour une mesure representative (simulation seulement)}';

    protected $description = 'Détecte et lie automatiquement (source=auto) les fiches de glossaire pour les actualités publiées sans terme lié';

    public function handle(NewsToolSyncAction $action): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
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

        $requete = NewsArticle::published()->whereDoesntHave('terms');
        $articles = ($echantillon ? $requete->inRandomOrder() : $requete->orderBy('id'))
            ->limit($limit)
            ->get();

        if ($articles->isEmpty()) {
            $this->info('Aucune actualité publiée sans terme de glossaire lié - rien à faire.');

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
            // MCP: SELF (<5 lignes)
            if ($dryRun) {
                $count = $action->suggestGlossaryTermIds($article)->count();
            } else {
                $action->suggest($article);
                $count = $article->terms()->count();
            }

            if ($count > 0) {
                $reparables++;

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

            $processed++;
        }

        $remaining = NewsArticle::published()->whereDoesntHave('terms')->count();

        if ($dryRun) {
            // Deux populations à ne pas confondre : une fiche sans terme lié n'est pas
            // forcément un défaut. Une actualité sur une politique publique n'a aucune raison
            // de mentionner une fiche du glossaire ; seules les fiches pour lesquelles
            // suggestGlossaryTermIds() propose quelque chose sont réellement réparables.
            $sansSuggestion = $processed - $reparables;
            $nature = $echantillon ? 'tirées AU HASARD' : 'les plus anciennes par identifiant';
            $this->info("[simulation] {$processed} fiche(s) examinée(s), {$nature} : {$reparables} mentionnent réellement une fiche de glossaire (réparables), {$sansSuggestion} n'en mentionnent aucune (absence normale). Aucune écriture, aucune purge.");
            $this->comment("Total de fiches publiées sans terme de glossaire lié, toutes causes confondues : {$remaining}.");
        } else {
            $this->info("{$processed} actualité(s) traitée(s), {$totalAttached} terme(s) de glossaire auto-lié(s). {$remaining} restante(s) sans terme.");

            if ($remaining > 0) {
                $this->comment('Relancer la commande pour continuer le rattrapage.');
            }
        }

        return self::SUCCESS;
    }
}
