<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Models\Product;

class SyncGelatoPricesCommand extends Command
{
    protected $signature = 'shop:sync-prices {--force : Mettre a jour meme si le prix n\'a pas change}';

    protected $description = 'Synchronise les prix des produits depuis l\'API Gelato';

    public function handle(): int
    {
        $products = Product::whereNotNull('gelato_product_id')->get();
        $results = [];
        $force = $this->option('force');

        foreach ($products as $product) {
            try {
                $oldCost = $product->metadata['cost_base'] ?? null;
                $oldPrice = (float) $product->price;

                $response = Http::withHeaders([
                    'X-API-KEY' => config('shop.gelato.api_key'),
                ])->get("https://product.gelatoapis.com/v3/products/{$product->gelato_product_id}/prices", [
                    'country' => 'CA',
                ]);

                $data = $response->json();
                $newCost = $data[0]['price'] ?? null;

                if ($newCost === null) {
                    $results[] = [$product->name, $oldCost, '-', $oldPrice, '-', 'Erreur API'];
                    continue;
                }

                $newCost = round((float) $newCost, 2);
                $costChanged = abs(($oldCost ?? 0) - $newCost) > 0.01;

                if ($costChanged || $force) {
                    $metadata = $product->metadata ?? [];
                    $metadata['cost_base'] = $newCost;
                    $metadata['cost_currency'] = 'USD';
                    $product->metadata = $metadata;
                    $newPrice = Product::smartPrice($newCost, $product->category ?? 'default');
                    $product->price = $newPrice;
                    $product->save();

                    $results[] = [$product->name, $oldCost, $newCost, $oldPrice, $newPrice, 'Mis a jour'];
                } else {
                    $results[] = [$product->name, $oldCost, $newCost, $oldPrice, $oldPrice, 'Inchange'];
                }
            } catch (\Exception $e) {
                $results[] = [$product->name, $product->metadata['cost_base'] ?? '-', '-', $product->price, '-', 'Erreur: ' . $e->getMessage()];
            }
        }

        $this->table(['Produit', 'Ancien cout', 'Nouveau cout', 'Ancien prix', 'Nouveau prix', 'Status'], $results);
        $this->info(count($results) . ' produits traites.');

        return self::SUCCESS;
    }
}
