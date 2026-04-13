<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Models\Product;

class SyncGelatoPricesCommand extends Command
{
    protected $signature = 'shop:sync-prices {--force : Mettre a jour meme si le prix n\'a pas change}';

    protected $description = 'Synchronise les prix des produits depuis l\'API Gelato (par taille)';

    public function handle(): int
    {
        $products = Product::all()->filter(fn ($p) => ! empty($p->variants));
        $results = [];
        $force = $this->option('force');

        foreach ($products as $product) {
            try {
                $sizes = $product->metadata['sizes'] ?? [];
                $variants = $product->variants ?? [];

                if (empty($sizes) || empty($variants)) {
                    $results[] = [$product->name, '-', '-', $product->price, '-', 'Pas de tailles'];
                    continue;
                }

                // Prendre le gelato_uid de la première variante (taille M)
                $baseUid = $variants[0]['gelato_uid'] ?? null;
                if (! $baseUid) {
                    $results[] = [$product->name, '-', '-', $product->price, '-', 'Pas de UID Gelato'];
                    continue;
                }

                // Récupérer le coût pour chaque taille
                $gelatoCosts = [];
                $sizePrices = [];
                foreach ($sizes as $size) {
                    $uid = preg_replace('/_gsi_[a-z0-9]+_/', '_gsi_' . strtolower($size) . '_', $baseUid);
                    $cost = $this->fetchCost($uid);
                    if ($cost !== null) {
                        $gelatoCosts[$size] = round($cost, 2);
                        $sizePrices[$size] = Product::smartPrice($cost, $product->category ?? 'default');
                    }
                }

                if (empty($sizePrices)) {
                    $results[] = [$product->name, '-', '-', $product->price, '-', 'Erreur API toutes tailles'];
                    continue;
                }

                // Vérifier si les prix ont changé
                $oldPrices = $product->metadata['size_prices_cad'] ?? [];
                $hasChanged = $force || $oldPrices != $sizePrices;

                if (! $hasChanged) {
                    $results[] = [$product->name, json_encode($gelatoCosts), '-', $product->price, '-', 'Inchange'];
                    continue;
                }

                // Mettre à jour size_prices dans chaque variante couleur
                $updatedVariants = $variants;
                foreach ($updatedVariants as &$v) {
                    $v['size_prices'] = $sizePrices;
                }

                // Mettre à jour le produit
                $newBasePrice = $sizePrices['M'] ?? $sizePrices[array_key_first($sizePrices)];
                $oldPrice = $product->price;

                $metadata = $product->metadata ?? [];
                $metadata['gelato_costs_usd'] = $gelatoCosts;
                $metadata['size_prices_cad'] = $sizePrices;
                $metadata['pricing_updated_at'] = now()->toIso8601String();

                $product->update([
                    'price' => $newBasePrice,
                    'variants' => $updatedVariants,
                    'metadata' => $metadata,
                ]);

                $results[] = [$product->name, count($sizePrices) . ' tailles', json_encode(array_map(fn ($p) => number_format($p, 2), $sizePrices)), $oldPrice, $newBasePrice, 'Mis a jour'];
            } catch (\Exception $e) {
                $results[] = [$product->name, '-', '-', $product->price, '-', 'Erreur: ' . $e->getMessage()];
            }
        }

        $this->table(['Produit', 'Couts', 'Prix par taille', 'Ancien prix', 'Nouveau prix', 'Status'], $results);
        $this->info(count($results) . ' produits traites.');

        return self::SUCCESS;
    }

    private function fetchCost(string $productUid): ?float
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => config('shop.gelato.api_key'),
            ])->get("https://product.gelatoapis.com/v3/products/{$productUid}/prices", [
                'country' => 'CA',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return isset($data[0]['price']) ? (float) $data[0]['price'] : null;
            }
        } catch (\Exception $e) {
            $this->components->warn("Erreur API prix {$productUid}: {$e->getMessage()}");
        }

        return null;
    }
}
