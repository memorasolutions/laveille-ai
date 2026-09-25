<?php

declare(strict_types=1);

namespace Modules\Signature\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Signature\Services\SignaturePurgeQuarantineService;

/**
 * Vide DÉFINITIVEMENT l'archive de purge et la quarantaine (B2) pour tout ce qui dépasse la
 * fenêtre de rétention ('signature.quarantine_retention_days', 30 jours par défaut). Planifiée
 * quotidiennement (routes/console.php) - c'est la SEULE commande qui efface réellement une donnée
 * déjà purgée ; avant cette commande, tout reste reconstructible via
 * signature:restore-from-quarantine.
 */
class PurgeSignatureQuarantineCommand extends Command
{
    protected $signature = 'signature:purge-quarantaine';

    protected $description = "Vide definitivement la quarantaine et l'archive de purge des signatures apres le delai de retention (30 jours par defaut)";

    public function handle(SignaturePurgeQuarantineService $quarantine): int
    {
        $retentionDays = (int) config('signature.quarantine_retention_days', 30);
        $cutoff = Carbon::now()->subDays($retentionDays);

        $result = $quarantine->purgeOlderThan($cutoff);

        $this->info("Quarantaine videe : {$result['files']} archive(s) du jour, {$result['folders']} dossier(s) de quarantaine.");

        return self::SUCCESS;
    }
}
