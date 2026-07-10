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
    protected $signature = 'news:backfill-auto-tools {--limit=200 : Nombre maximal d\'actualités traitées par exécution}';

    protected $description = 'Détecte et lie automatiquement (source=auto) les outils annuaire pour les actualités publiées sans outil lié';

    public function handle(NewsToolSyncAction $action): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $articles = NewsArticle::published()
            ->whereDoesntHave('tools')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($articles->isEmpty()) {
            $this->info('Aucune actualité publiée sans outil lié - rien à faire.');

            return self::SUCCESS;
        }

        $processed = 0;
        $totalAttached = 0;

        foreach ($articles as $article) {
            $suggested = $action->suggest($article);

            if ($suggested->isNotEmpty()) {
                $count = $action->attachAuto($article, $suggested);
                $totalAttached += $count;
                $this->line("  article #{$article->id} : {$count} outil(s)");
            }

            $processed++;
        }

        $remaining = NewsArticle::published()->whereDoesntHave('tools')->count();

        $this->info("{$processed} actualité(s) traitée(s), {$totalAttached} outil(s) auto-lié(s). {$remaining} restante(s) sans outil.");

        if ($remaining > 0) {
            $this->comment('Relancer la commande pour continuer le rattrapage.');
        }

        return self::SUCCESS;
    }
}
