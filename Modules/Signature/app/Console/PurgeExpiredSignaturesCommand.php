<?php

declare(strict_types=1);

namespace Modules\Signature\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignaturePurgeService;

/**
 * Purge à 6 mois (config signature.retention_months) - SAUF si une image a été chargée dans les 6
 * derniers mois (Signature::isEligibleForPurge(), section 6.7). Ce que la purge fait exactement
 * (section 6.3, réalisé par Modules\Signature\Services\SignaturePurgeService, DRY avec la
 * suppression explicite d'un membre - M3.4) :
 *   - le contenu personnel (`content`, `reminder_email`) est archivé en quarantaine PUIS effacé ;
 *   - chaque fichier image est DÉPLACÉ en quarantaine (B2) - jamais supprimé sans filet ;
 *   - la ligne `signatures` et les lignes `signature_images` restent (coquille technique vide) -
 *     largeur/hauteur conservées pour continuer à répondre par un rectangle transparent aux bonnes
 *     dimensions sur une vieille URL déjà distribuée dans un courriel (section 6.4).
 *
 * Garde-fous « ne jamais supprimer à tort » (section 6.5, patron exact de
 * Modules\Decido\Console\PurgeExpiredPollsCommand) : --dry-run obligatoire à la première
 * exécution en production (revue manuelle avant le mode réel), traitement par lot plafonné à 500.
 *
 * Deux gardes AVANT la purge réelle (corrections B1/M1.3/M1.4) :
 *   - B1 : une signature SANS ancre temporelle connue (Signature::purgeAnchor() nul) est ignorée
 *     et journalisée - jamais purgée sans preuve d'ancienneté ;
 *   - M1.4 : une signature de MEMBRE (compte encore existant) n'est jamais purgée automatiquement
 *     (voir Signature::isEligibleForPurge()) ;
 *   - M1.3 : une signature avec un rappel opt-in (reminder_email) n'est purgée que si ce rappel a
 *     déjà été envoyé ET qu'au moins 14 jours se sont écoulés depuis - l'avertissement est une
 *     CONDITION de la purge, jamais un geste optionnel qui peut manquer sans conséquence.
 */
class PurgeExpiredSignaturesCommand extends Command
{
    protected $signature = 'signature:purge-expired {--dry-run : Liste les signatures qui seraient purgées, sans rien supprimer}';

    protected $description = "Purge les signatures de courriel inactives depuis 6 mois (contenu et images, avec filet de quarantaine 30 jours) - jamais si une image a été chargée récemment ou si le rappel opt-in n'a pas encore eu 14 jours";

    public function handle(SignaturePurgeService $purgeService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $months = (int) config('signature.retention_months', 6);
        $disk = (string) config('signature.disk', 'public');
        $candidateIds = [];

        // Aucun filtre SQL sur last_owner_activity_at/last_image_loaded_on ici : les signaux et
        // leur combinaison sont évalués en PHP par Signature::isEligibleForPurge(), plus sûr qu'une
        // clause WHERE composée à dupliquer et risquer de désynchroniser de cette même méthode.
        Signature::where('status', '!=', Signature::STATUS_PURGED)
            ->chunkById(500, function ($chunk) use (&$candidateIds, $months) {
                foreach ($chunk as $sig) {
                    // B1 : sans ancre temporelle connue, on n'a AUCUNE preuve d'ancienneté - la
                    // ligne est ignorée par prudence plutôt que traitée comme "éligible par
                    // défaut" (c'était exactement le bug inversé).
                    if ($sig->purgeAnchor() === null) {
                        Log::warning("signature:purge-expired - signature #{$sig->id} sans ancre connue (last_owner_activity_at et created_at NULS), ignorée par prudence.");

                        continue;
                    }

                    if ($sig->isEligibleForPurge($months) && $this->warningConditionSatisfied($sig)) {
                        $candidateIds[] = $sig->id;
                    }
                }
            });

        if ($dryRun) {
            $this->info('[dry-run] '.count($candidateIds)." signature(s) seraient purgées : ".implode(', ', $candidateIds));

            return self::SUCCESS;
        }

        $purged = 0;
        foreach (array_chunk($candidateIds, 500) as $batch) {
            foreach (Signature::whereIn('id', $batch)->with('images')->get() as $sig) {
                $purgeService->purge($sig, $disk, 'purge-expired');
                $purged++;
            }
        }

        $this->info("Signatures purgées : {$purged}.");

        return self::SUCCESS;
    }

    /**
     * M1.3 : l'avertissement devient une CONDITION de la purge pour toute signature qui a un
     * destinataire de rappel connu (reminder_email - une signature de MEMBRE est déjà exclue en
     * amont par Signature::isEligibleForPurge(), M1.4). Sans destinataire connu, rien à
     * conditionner : la règle d'inactivité de 6 mois suffit à elle seule.
     */
    private function warningConditionSatisfied(Signature $sig): bool
    {
        if ($sig->reminder_email === null) {
            return true;
        }

        return $sig->reminder_sent_at !== null
            && $sig->reminder_sent_at->lessThan(now()->subDays(14));
    }
}
