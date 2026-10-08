<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use Illuminate\Support\Facades\DB;
use Modules\Shop\Models\Product;

/**
 * Pilote la machine à états du fichier d'impression.
 * Toute règle de commandabilité passe par ici (une seule source de vérité).
 */
class PrintFileService
{
    public function __construct(private PrintPrepClient $client) {}

    /**
     * Prépare l'illustration via le moteur puis enregistre le résultat (-> PREPARED).
     *
     * @param array<string,mixed> $payload artworkUrl|artworkBase64, printArea, productUid, overrideDimensionsMm...
     * @throws PrintPrepException NON_CONFORME / ZONE_INCONNUE : à remonter à l'admin, jamais deviné
     */
    public function prepare(Product $product, array $payload, ?string $variantUid = null): PrintFile
    {
        $result = $this->client->prepare($payload + ['printArea' => 'front']);

        return $this->recordPrepared($product, $result, $variantUid, $payload['printArea'] ?? 'front', $payload['productUid'] ?? null);
    }

    /**
     * Enregistre un résultat "prepared". Un hash différent de l'existant réinvalide
     * toute approbation (retour PREPARED, mockup et approbation effacés).
     *
     * @param array<string,mixed> $result
     */
    public function recordPrepared(Product $product, array $result, ?string $variantUid = null, string $printArea = 'front', ?string $productUid = null): PrintFile
    {
        $publicUrl = $result['publicUrl'] ?? null;
        if (! PrintFile::isAbsoluteHttpsUrl(is_string($publicUrl) ? $publicUrl : null)) {
            throw new PrintFileNotApprovedException('URL du fichier d\'impression invalide : une URL absolue https:// est exigée.');
        }
        $productUid = $productUid ?? ($result['productUid'] ?? null);

        $file = PrintFile::firstOrNew([
            'product_id' => $product->id, 'variant_key' => (string) ($variantUid ?? ''), 'print_area' => $printArea,
        ]);
        $file->variant_uid = $variantUid;

        $newHash = (string) ($result['contentHash'] ?? '');
        $changed = ! $file->exists
            || $file->print_file_hash !== $newHash
            || ($productUid !== null && $file->product_uid !== $productUid);

        // Même hash sur un fichier déjà approuvé : l'enregistrement approuvé reste IMMUABLE (URL, version moteur...).
        if (! $changed && $file->status->isOrderable()) {
            return $file;
        }

        $file->fill([
            'print_file_hash' => $newHash,
            'product_uid' => $productUid ?? $file->product_uid,
            'public_url' => $publicUrl,
            'rel_path' => $result['relPath'] ?? null,
            'engine_version' => $result['engineVersion'] ?? null,
            'width_mm' => $result['validation']['widthMm'] ?? $file->width_mm,
            'height_mm' => $result['validation']['heightMm'] ?? $file->height_mm,
            'dpi' => isset($result['validation']['effectiveDpi']) ? (int) round($result['validation']['effectiveDpi']) : $file->dpi,
            'zone_version' => $result['derivativeKey'] ?? $file->zone_version,
            'profile' => $result['validation']['profile'] ?? $file->profile,
            'source_hash' => $result['sourceHash'] ?? $file->source_hash,
        ]);

        if ($changed) {
            $file->forceFill([
                'status' => PrintFileStatus::Prepared,
                'mockup_path' => $result['mockupPreviewPath'] ?? null,
                'approved_hash' => null,
                'approved_at' => null,
                'approved_by' => null,
            ]);
        }

        $file->save();

        return $file;
    }

    /**
     * Mêmes garanties que approve() : transaction + verrou de ligne + revalidation du hash vu par l'appelant,
     * pour qu'un statut MockupReady ne porte jamais l'aperçu d'un ancien hash (course avec un re-prepare).
     */
    public function markMockupReady(PrintFile $file, ?string $mockupPath = null): PrintFile
    {
        return DB::transaction(function () use ($file, $mockupPath) {
            $locked = $this->lockAndRevalidate($file, [PrintFileStatus::Prepared], PrintFileStatus::MockupReady);
            $path = $mockupPath ?? $locked->mockup_path;
            if (empty($path)) {
                throw new PrintFileNotApprovedException('Aucun aperçu (mockup) : impossible de passer à MOCKUP_READY.');
            }

            $locked->forceFill(['status' => PrintFileStatus::MockupReady, 'mockup_path' => $path])->save();

            return $locked;
        });
    }

    /**
     * Seule voie vers APPROVED. Sous transaction + verrou de ligne : on revalide l'état ET le hash
     * (celui que l'appelant a vu/approuvé) contre la ligne verrouillée, pour exclure une course
     * avec un re-prepare concurrent.
     */
    public function approve(PrintFile $file, ?int $userId): PrintFile
    {
        return DB::transaction(function () use ($file, $userId) {
            $locked = PrintFile::whereKey($file->getKey())->lockForUpdate()->first()
                ?? throw new PrintFileNotApprovedException('Fichier d\'impression introuvable.');

            $this->requireStatus($locked, [PrintFileStatus::MockupReady], PrintFileStatus::Approved);

            if (! hash_equals((string) $locked->print_file_hash, (string) $file->print_file_hash)) {
                throw new PrintFileNotApprovedException('Le fichier a changé pendant l\'approbation (hash différent) : nouvel aperçu requis.');
            }
            if (! PrintFile::isAbsoluteHttpsUrl($locked->public_url)) {
                throw new PrintFileNotApprovedException('URL du fichier d\'impression invalide : approbation refusée.');
            }

            $locked->forceFill([
                'status' => PrintFileStatus::Approved,
                'approved_hash' => $locked->print_file_hash,
                'approved_at' => now(),
                'approved_by' => $userId,
            ])->save();

            return $locked;
        });
    }

    public function markSellable(PrintFile $file): PrintFile
    {
        return DB::transaction(function () use ($file) {
            $locked = $this->lockAndRevalidate($file, [PrintFileStatus::Approved], PrintFileStatus::Sellable);
            if (! $locked->isOrderable()) {
                throw new PrintFileNotApprovedException('Approbation invalide (hash modifié) : impossible de rendre vendable.');
            }

            $locked->forceFill(['status' => PrintFileStatus::Sellable])->save();

            return $locked;
        });
    }

    /**
     * Relit la ligne sous verrou (à appeler DANS une transaction), exige le statut attendu et un hash identique à celui vu par l'appelant.
     *
     * @param list<PrintFileStatus> $allowed
     */
    private function lockAndRevalidate(PrintFile $file, array $allowed, PrintFileStatus $target): PrintFile
    {
        $locked = PrintFile::whereKey($file->getKey())->lockForUpdate()->first()
            ?? throw new PrintFileNotApprovedException('Fichier d\'impression introuvable.');

        $this->requireStatus($locked, $allowed, $target);

        if (! hash_equals((string) $locked->print_file_hash, (string) $file->print_file_hash)) {
            throw new PrintFileNotApprovedException('Le fichier a changé (hash différent) : nouvel aperçu requis.');
        }

        return $locked;
    }

    /** Fichier commandable pour un produit/variante (variante précise d'abord, sinon produit entier), ou null. */
    public function findOrderable(int $productId, ?string $variantUid, string $printArea = 'front'): ?PrintFile
    {
        $candidates = PrintFile::where('product_id', $productId)
            ->where('print_area', $printArea)
            ->where(fn ($q) => $q->where('variant_key', '')->when($variantUid, fn ($q2) => $q2->orWhere('variant_key', $variantUid)))
            ->get()
            // la variante précise prime sur le fichier "tout le produit"
            ->sortByDesc(fn (PrintFile $f) => $f->variant_uid === null ? 0 : 1);

        return $candidates->first(fn (PrintFile $f) => $f->isOrderable());
    }

    /**
     * Le productUid de l'item commandé doit égaler celui utilisé à la préparation (zone/variante) ;
     * un uid de préparation inconnu est refusé (fail-closed).
     *
     * @throws PrintFileNotApprovedException
     */
    public function assertOrderable(int $productId, ?string $variantUid, ?string $productUid = null): PrintFile
    {
        $file = $this->findOrderable($productId, $variantUid)
            ?? throw new PrintFileNotApprovedException("Aucun fichier d'impression approuvé (produit #{$productId}, variante ".($variantUid ?: '-').').');

        if ($productUid !== null && ($file->product_uid === null || $file->product_uid !== $productUid)) {
            throw new PrintFileNotApprovedException("Le fichier approuvé a été préparé pour un autre productUid (préparé : ".($file->product_uid ?: 'inconnu').", commandé : {$productUid}).");
        }

        return $file;
    }

    /** @param list<PrintFileStatus> $allowed */
    private function requireStatus(PrintFile $file, array $allowed, PrintFileStatus $target): void
    {
        if (! in_array($file->status, $allowed, true)) {
            throw new PrintFileNotApprovedException("Transition interdite {$file->status->value} -> {$target->value}.");
        }
    }
}
