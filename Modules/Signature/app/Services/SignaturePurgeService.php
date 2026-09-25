<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

use Illuminate\Support\Facades\Log;
use Modules\Signature\Models\Signature;

/**
 * Service PARTAGÉ (M3.4, DRY strict) de la seule et unique façon d'effacer le contenu d'une
 * signature - utilisé À LA FOIS par la purge automatique
 * (Modules\Signature\Console\PurgeExpiredSignaturesCommand) ET par la suppression explicite d'un
 * membre (Modules\Signature\Http\Controllers\UserSignatureController::destroy()). Aucune des deux
 * routes n'efface plus jamais directement un fichier ou une ligne de contenu : tout passe
 * d'abord par la quarantaine (B2, SignaturePurgeQuarantineService) avant que la coquille
 * technique (id, largeur/hauteur des images) ne soit vidée.
 */
final class SignaturePurgeService
{
    public function __construct(private readonly SignaturePurgeQuarantineService $quarantine) {}

    public function purge(Signature $signature, string $disk, string $reason): void
    {
        if (! $signature->relationLoaded('images')) {
            $signature->load('images');
        }

        // B2 : archive + déplacement en quarantaine AVANT toute suppression réelle - dans cet
        // ordre, jamais l'inverse (une suppression qui échouerait après l'archivage laisse au pire
        // un doublon récupérable, jamais une perte).
        $this->quarantine->quarantine($signature, $disk, $reason);

        foreach ($signature->images as $image) {
            // Largeur/hauteur CONSERVÉES (section 6.4) - seul le chemin disparaît, pour que la
            // route /signature-assets/{public_id}.{ext} continue de répondre par un rectangle
            // transparent aux dimensions exactes plutôt que de casser la mise en page d'un vieux
            // courriel déjà distribué.
            $image->update([
                'path' => null,
                'purged_at' => now(),
            ]);
        }

        $signature->update([
            'content' => [],
            'reminder_email' => null,
            'status' => Signature::STATUS_PURGED,
            'purged_at' => now(),
        ]);

        Log::info("signature: signature #{$signature->id} purgée ({$reason}) - contenu et images en quarantaine opérateur pour ".((int) config('signature.quarantine_retention_days', 30))." jours.");
    }
}
