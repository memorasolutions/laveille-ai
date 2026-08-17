<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;

/**
 * Filet de vérification (addendum "purge garantie sur tous les chemins de publication",
 * 2026-08-17, exigence du propriétaire : « important de ne jamais garder les articles
 * originaux, important de vérifier ») - trouve toute fiche publiée (is_published=true) dont
 * 'internal_source_text' est encore non NULL, PEU IMPORTE comment elle a été publiée, et la
 * purge.
 *
 * Complète, sans remplacer, les deux gardes préventives déjà en place :
 * - NewsCompositionController::publish() (bouton Publier-et-purger, gardes de prérequis)
 * - AdminNewsController::toggleArticle() (bascule rapide)
 * ...qui appellent toutes deux NewsArticle::publishAndPurgeSource() (DRY, une seule
 * implémentation de la purge). Cette commande est le rattrapage : même un chemin de publication
 * FUTUR qui oublierait d'appeler publishAndPurgeSource() (nouveau code, import, correction
 * manuelle en base) serait rattrapé sous 24 h par l'exécution quotidienne planifiée
 * (routes/console.php, ~07h05, avant le digest de 07h15).
 *
 * Idempotente et sans argument : un second passage immédiat ne trouve plus rien à purger (0).
 * Ne touche JAMAIS une fiche non publiée (brouillon) - un texte source non purgé sur un
 * brouillon est l'état normal et attendu, pas une anomalie.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class VerifySourcePurgeCommand extends Command
{
    protected $signature = 'news:verify-source-purge';

    protected $description = 'Filet de vérification : purge le texte source intégral de toute fiche déjà publiée qui le porterait encore.';

    public function handle(): int
    {
        $stragglers = NewsArticle::query()
            ->where('is_published', true)
            ->whereNotNull('internal_source_text')
            ->get(['id', 'slug', 'internal_source_text']);

        if ($stragglers->isEmpty()) {
            $this->info('Aucune fiche publiée ne porte encore de texte source intégral. Rien à purger.');

            return self::SUCCESS;
        }

        foreach ($stragglers as $article) {
            $purgedLength = mb_strlen((string) $article->internal_source_text);

            // ACTION : purge SEULE - is_published/published_at déjà corrects (la fiche est déjà
            // publiée), provenance/paires/acquisition survivent, même garde-fou que
            // destroySourceText() et publishAndPurgeSource().
            // MCP: SELF (<5 lignes)
            // RAISON: rattrapage, pas une republication - ne touche à rien d'autre.
            $article->update(['internal_source_text' => null]);

            Log::channel('composition')->info('news:verify-source-purge - fiche rattrapée', [
                'article_id' => $article->id,
                'slug' => $article->slug,
                'purged_length' => $purgedLength,
            ]);
        }

        $count = $stragglers->count();
        $this->warn("{$count} fiche(s) publiée(s) portaient encore un texte source intégral - purgée(s).");

        return self::SUCCESS;
    }
}
