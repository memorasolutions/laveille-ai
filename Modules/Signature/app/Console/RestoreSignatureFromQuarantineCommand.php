<?php

declare(strict_types=1);

namespace Modules\Signature\Console;

use Illuminate\Console\Command;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignaturePurgeQuarantineService;

/**
 * Restaure une signature purgée (automatiquement ou par un membre) depuis la quarantaine
 * opérateur (B2), dans la fenêtre de 'quarantine_retention_days' jours (30 par défaut) - la SEULE
 * façon de récupérer un contenu purgé. Usage opérateur (terminal cPanel), aucune interface admin
 * dédiée : le filet de rollback exigé par la règle « jamais de suppression de données
 * utilisateurs sans filet » n'implique pas une fonctionnalité en libre-service pour l'utilisateur
 * final, qui n'a jamais accès à sa propre quarantaine.
 */
class RestoreSignatureFromQuarantineCommand extends Command
{
    protected $signature = 'signature:restore-from-quarantine {signature_id : Identifiant de la signature a restaurer}';

    protected $description = "Restaure une signature purgee depuis la quarantaine operateur (fenetre de 30 jours), a partir de l'archive et des fichiers deplaces";

    public function handle(SignaturePurgeQuarantineService $quarantine): int
    {
        $id = (int) $this->argument('signature_id');
        $signature = Signature::find($id);

        if (! $signature) {
            $this->error("Signature #{$id} introuvable.");

            return self::FAILURE;
        }

        if (! $signature->isPurged()) {
            $this->error("Signature #{$id} n'est pas purgee, rien a restaurer.");

            return self::FAILURE;
        }

        $restored = $quarantine->restore($signature);

        if (! $restored) {
            $this->error("Aucune archive de quarantaine trouvee pour la signature #{$id} (peut-etre deja videe apres le delai de retention).");

            return self::FAILURE;
        }

        $this->info("Signature #{$id} restauree depuis la quarantaine.");

        return self::SUCCESS;
    }
}
