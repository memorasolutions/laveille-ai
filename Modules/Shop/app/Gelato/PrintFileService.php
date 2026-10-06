<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

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

        return $this->recordPrepared($product, $result, $variantUid, $payload['printArea'] ?? 'front');
    }

    /**
     * Enregistre un résultat "prepared". Un hash différent de l'existant réinvalide
     * toute approbation (retour PREPARED, mockup et approbation effacés).
     *
     * @param array<string,mixed> $result
     */
    public function recordPrepared(Product $product, array $result, ?string $variantUid = null, string $printArea = 'front'): PrintFile
    {
        $file = PrintFile::firstOrNew([
            'product_id' => $product->id, 'variant_uid' => $variantUid, 'print_area' => $printArea,
        ]);

        $newHash = (string) ($result['contentHash'] ?? '');
        $changed = ! $file->exists || $file->print_file_hash !== $newHash;

        $file->fill([
            'print_file_hash' => $newHash,
            'public_url' => $result['publicUrl'] ?? null,
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
            $file->fill([
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

    public function markMockupReady(PrintFile $file, ?string $mockupPath = null): PrintFile
    {
        $this->requireStatus($file, [PrintFileStatus::Prepared], PrintFileStatus::MockupReady);
        $path = $mockupPath ?? $file->mockup_path;
        if (empty($path)) {
            throw new PrintFileNotApprovedException('Aucun aperçu (mockup) : impossible de passer à MOCKUP_READY.');
        }

        $file->update(['status' => PrintFileStatus::MockupReady, 'mockup_path' => $path]);

        return $file;
    }

    public function approve(PrintFile $file, ?int $userId): PrintFile
    {
        $this->requireStatus($file, [PrintFileStatus::MockupReady], PrintFileStatus::Approved);

        $file->update([
            'status' => PrintFileStatus::Approved,
            'approved_hash' => $file->print_file_hash,
            'approved_at' => now(),
            'approved_by' => $userId,
        ]);

        return $file;
    }

    public function markSellable(PrintFile $file): PrintFile
    {
        $this->requireStatus($file, [PrintFileStatus::Approved], PrintFileStatus::Sellable);
        if (! $file->isOrderable()) {
            throw new PrintFileNotApprovedException('Approbation invalide (hash modifié) : impossible de rendre vendable.');
        }

        $file->update(['status' => PrintFileStatus::Sellable]);

        return $file;
    }

    /** Fichier commandable pour un produit/variante (variante précise d'abord, sinon produit entier), ou null. */
    public function findOrderable(int $productId, ?string $variantUid, string $printArea = 'front'): ?PrintFile
    {
        $candidates = PrintFile::where('product_id', $productId)
            ->where('print_area', $printArea)
            ->where(fn ($q) => $q->whereNull('variant_uid')->when($variantUid, fn ($q2) => $q2->orWhere('variant_uid', $variantUid)))
            ->get()
            // la variante précise prime sur le fichier "tout le produit"
            ->sortByDesc(fn (PrintFile $f) => $f->variant_uid === null ? 0 : 1);

        return $candidates->first(fn (PrintFile $f) => $f->isOrderable());
    }

    /** @throws PrintFileNotApprovedException */
    public function assertOrderable(int $productId, ?string $variantUid): PrintFile
    {
        return $this->findOrderable($productId, $variantUid)
            ?? throw new PrintFileNotApprovedException("Aucun fichier d'impression approuvé (produit #{$productId}, variante ".($variantUid ?: '-').').');
    }

    /** @param list<PrintFileStatus> $allowed */
    private function requireStatus(PrintFile $file, array $allowed, PrintFileStatus $target): void
    {
        if (! in_array($file->status, $allowed, true)) {
            throw new PrintFileNotApprovedException("Transition interdite {$file->status->value} -> {$target->value}.");
        }
    }
}
