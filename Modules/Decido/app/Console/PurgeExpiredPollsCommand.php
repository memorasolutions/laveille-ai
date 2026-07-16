<?php

declare(strict_types=1);

namespace Modules\Decido\Console;

use Illuminate\Console\Command;
use Modules\Decido\Models\Poll;

/**
 * Purge les sondages clôturés dont expires_at est dépassé (config('decido.expiration_months_after_close')).
 *
 * Le champ expires_at était écrit à la clôture (PollManageController::close()) mais jamais lu
 * nulle part ailleurs dans le module - politique de rétention morte, trouvée par une passe
 * adversariale indépendante (skill /100, round 5). Pattern calqué sur
 * Modules\ShortUrl\Console\CleanupExpiredCommand.
 */
class PurgeExpiredPollsCommand extends Command
{
    protected $signature = 'decido:purge-expired';

    protected $description = 'Supprime les sondages Decido clotures dont la date d\'expiration est depassee';

    public function handle(): int
    {
        // Round 7 (skill /100) : chargeait tous les sondages expirés en memoire (->get()) puis
        // emettait une requete DELETE individuelle par sondage en boucle - defaut de conception
        // qui empire lineairement avec le volume. Remplace par un DELETE en masse : aucun hook
        // Eloquent deleting/deleted n'est enregistre sur Poll (verifie), et les cascades vers
        // options/votes sont au niveau contrainte FK DB (cascadeOnDelete), pas au niveau Eloquent
        // - donc un DELETE en masse se comporte de facon strictement identique et reste sur.
        $query = Poll::where('status', 'closed')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        $count = $query->count();
        $query->delete();

        $this->info("Sondages Decido expires supprimes : {$count}.");

        return self::SUCCESS;
    }
}
