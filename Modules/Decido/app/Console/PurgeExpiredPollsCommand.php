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
        $expired = Poll::where('status', 'closed')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $poll) {
            $poll->delete();
        }

        $count = $expired->count();
        $this->info("Sondages Decido expires supprimes : {$count}.");

        return self::SUCCESS;
    }
}
