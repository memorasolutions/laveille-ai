<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;

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
 */
class BackfillAutoToolDetectionCommand extends Command
{
    protected $signature = 'news:backfill-auto-tools {--limit=200 : Nombre maximal d\'actualités traitées par exécution} {--dry-run : Mesurer sans rien écrire ni purger} {--echantillon : Tirer les fiches AU HASARD au lieu des plus anciennes, pour une mesure representative (simulation seulement)}';

    protected $description = 'Détecte et lie automatiquement (source=auto) les outils annuaire pour les actualités publiées sans outil lié';

    public function handle(NewsToolSyncAction $action): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        // ACTION : tirage AU HASARD, reserve a la simulation.
        // MCP: SELF (<5 lignes)
        // RAISON: en simulation rien n'est ecrit, donc deux appels successifs renvoient
        // exactement les MEMES premieres fiches par identifiant - c'est-a-dire les PLUS
        // ANCIENNES. Mesurer un taux sur cet echantillon, c'est mesurer le passe et le
        // presenter comme le tout. Le tirage aleatoire donne une proportion representative
        // en un seul appel, ce qui compte quand une execution complete depasse la limite
        // de temps du serveur. Interdit hors simulation : sur un vrai rattrapage, un ordre
        // aleatoire empeche de reprendre la ou l'on s'etait arrete.
        $echantillon = $dryRun && (bool) $this->option('echantillon');

        $requete = NewsArticle::published()->whereDoesntHave('tools');
        $articles = ($echantillon ? $requete->inRandomOrder() : $requete->orderBy('id'))
            ->limit($limit)
            ->get();

        if ($articles->isEmpty()) {
            $this->info('Aucune actualité publiée sans outil lié - rien à faire.');

            return self::SUCCESS;
        }

        $processed = 0;
        $totalAttached = 0;
        $reparables = 0;

        foreach ($articles as $article) {
            $suggested = $action->suggest($article);

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

            $processed++;
        }

        $remaining = NewsArticle::published()->whereDoesntHave('tools')->count();

        if ($dryRun) {
            // Deux populations à ne pas confondre : une fiche sans outil lié n'est pas
            // forcément un défaut. Une actualité sur une politique publique n'a aucune raison
            // de mentionner un outil de l'annuaire ; seules les fiches pour lesquelles
            // suggest() propose quelque chose sont réellement réparables.
            $sansSuggestion = $processed - $reparables;
            $nature = $echantillon ? 'tirées AU HASARD' : 'les plus anciennes par identifiant';
            $this->info("[simulation] {$processed} fiche(s) examinée(s), {$nature} : {$reparables} mentionnent réellement un outil de l'annuaire (réparables), {$sansSuggestion} n'en mentionnent aucun (absence normale). Aucune écriture, aucune purge.");
            $this->comment("Total de fiches publiées sans outil lié, toutes causes confondues : {$remaining}.");
        } else {
            $this->info("{$processed} actualité(s) traitée(s), {$totalAttached} outil(s) auto-lié(s). {$remaining} restante(s) sans outil.");

            if ($remaining > 0) {
                $this->comment('Relancer la commande pour continuer le rattrapage.');
            }
        }

        return self::SUCCESS;
    }
}
