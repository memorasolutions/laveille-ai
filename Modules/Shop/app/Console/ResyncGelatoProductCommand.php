<?php

declare(strict_types=1);

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Services\GelatoSyncService;

/**
 * Resync un produit local depuis Gelato store API ciblé par slug + store_product_id.
 *
 * Usage :
 *   php artisan shop:gelato-resync t-shirt-unisexe-gildan-5000 41394873-2eaf-4950-bafc-15f6769ede41
 *
 * Différence avec shop:sync-gelato (bulk) :
 *   - Ciblé sur 1 produit identifié par slug
 *   - Force mapping vers colonne dédiée gelato_store_product_id
 *   - Backup automatique de l'ancien gelato_product_id dans legacy_gelato_uid
 *   - Pricing par taille via SIZE_SURCHARGE (Gelato facture XL+)
 *   - Hex couleurs Gildan 5000 mai 2026
 */
class ResyncGelatoProductCommand extends Command
{
    protected $signature = 'shop:gelato-resync
        {slug : Slug du produit local (ex: t-shirt-unisexe-gildan-5000)}
        {gelato_store_product_id : UUID du store_product Gelato (ex: 41394873-2eaf-4950-bafc-15f6769ede41)}
        {--dry : Mode dry-run (affiche le résultat sans persister)}';

    protected $description = 'Resync ciblé d\'un produit local depuis Gelato store (1 produit, mapping propre)';

    public function handle(GelatoSyncService $service): int
    {
        if (! $service->isConfigured()) {
            $this->error('Gelato API non configuré (GELATO_API_KEY / GELATO_STORE_ID manquant)');
            return self::FAILURE;
        }

        $slug = (string) $this->argument('slug');
        $gelatoStoreProductId = (string) $this->argument('gelato_store_product_id');

        $this->info("→ Resync produit slug={$slug} vers gelato_store_product_id={$gelatoStoreProductId}");

        if ($this->option('dry')) {
            $storeId = config('shop.gelato.store_id');
            $gelato = $service->fetchStoreProduct($storeId, $gelatoStoreProductId);
            if (! $gelato) {
                $this->error('Fetch Gelato failed');
                return self::FAILURE;
            }
            $variants = $service->transformToLocalVariants($gelato, 20.99);
            $this->line('Colors found: ' . count($variants));
            foreach ($variants as $v) {
                $this->line("  · {$v['color']} ({$v['color_slug']}) — {$v['color_hex']} — " . count($v['size_prices']) . ' sizes');
            }
            return self::SUCCESS;
        }

        $result = $service->syncProductBySlug($slug, $gelatoStoreProductId);

        if (! ($result['ok'] ?? false)) {
            $this->error('Sync failed: ' . ($result['error'] ?? 'unknown'));
            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Produit ID', (string) $result['product_id']);
        $this->components->twoColumnDetail('Titre Gelato', (string) ($result['gelato_title'] ?? ''));
        $this->components->twoColumnDetail('Couleurs', (string) $result['colors']);
        $this->components->twoColumnDetail('Variants totaux', (string) $result['total_variants']);
        $this->info('✓ Sync OK');

        return self::SUCCESS;
    }
}
