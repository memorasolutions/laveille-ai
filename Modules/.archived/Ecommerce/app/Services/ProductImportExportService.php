<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;

class ProductImportExportService
{
    public function exportProducts(): string
    {
        $products = Product::with('variants')->get();
        $temp = fopen('php://temp', 'r+');

        fputcsv($temp, ['name', 'slug', 'price', 'is_active', 'sku', 'variant_price', 'stock', 'weight']);

        foreach ($products as $product) {
            if ($product->variants->isEmpty()) {
                fputcsv($temp, [
                    $product->name, $product->slug, $product->price,
                    $product->is_active ? '1' : '0', '', '', '', '',
                ]);
            } else {
                foreach ($product->variants as $variant) {
                    fputcsv($temp, [
                        $product->name, $product->slug, $product->price,
                        $product->is_active ? '1' : '0',
                        $variant->sku, $variant->price, $variant->stock, $variant->weight,
                    ]);
                }
            }
        }

        rewind($temp);
        $csv = stream_get_contents($temp);
        fclose($temp);

        return $csv;
    }

    /** @return array{created: int, updated: int, errors: array<string>} */
    public function importProducts(string $csv): array
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => []];
        $temp = fopen('php://temp', 'r+');
        fwrite($temp, $csv);
        rewind($temp);

        $headers = fgetcsv($temp);
        if ($headers === false) {
            return $results;
        }

        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);
        $row = 1;

        while (($data = fgetcsv($temp)) !== false) {
            $row++;

            if (count($data) !== count($headers)) {
                $results['errors'][] = "Ligne {$row} : nombre de colonnes incorrect.";

                continue;
            }

            $r = array_combine($headers, $data);

            if (empty($r['name']) || empty($r['slug']) || ! is_numeric($r['price'] ?? '')) {
                $results['errors'][] = "Ligne {$row} : name, slug et price requis.";

                continue;
            }

            try {
                DB::transaction(function () use ($r, &$results) {
                    $product = Product::firstOrNew(['slug' => $r['slug']]);
                    $isNew = ! $product->exists;

                    $product->name = $r['name'];
                    $product->price = (float) $r['price'];
                    $product->is_active = filter_var($r['is_active'] ?? '1', FILTER_VALIDATE_BOOLEAN);
                    $product->save();

                    $isNew ? $results['created']++ : $results['updated']++;

                    if (! empty($r['sku'])) {
                        $variant = ProductVariant::firstOrNew(['sku' => $r['sku']]);
                        $variant->product_id = $product->id;
                        $variant->price = (float) ($r['variant_price'] ?? $r['price']);
                        $variant->stock = (int) ($r['stock'] ?? 0);
                        $variant->weight = ! empty($r['weight']) ? (float) $r['weight'] : null;
                        $variant->is_active = true;
                        $variant->save();
                    }
                });
            } catch (\Throwable $e) {
                $results['errors'][] = "Ligne {$row} : {$e->getMessage()}";
            }
        }

        fclose($temp);

        return $results;
    }
}
