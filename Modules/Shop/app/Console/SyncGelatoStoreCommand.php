<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Shop\Models\Product;

class SyncGelatoStoreCommand extends Command
{
    protected $signature = 'shop:sync-gelato
        {--dry-run : Afficher les changements sans modifier la DB}
        {--force : Forcer la mise à jour même si inchangé}';

    protected $description = 'Synchronise les produits depuis le store Gelato vers la base de données';

    private const COLOR_HEX = [
        'black' => '#1a1a2e', 'navy' => '#1e3a5f', 'forest-green' => '#2d5a27',
        'dark-heather' => '#9ca3af', 'sport-grey' => '#9ca3af', 'red' => '#ef4444',
        'cherry-red' => '#dc2626', 'cardinal-red' => '#b91c1c', 'garnet' => '#7f1d1d',
        'maroon' => '#5a1a1a', 'royal' => '#2563eb', 'purple' => '#7c3aed',
        'heliconia' => '#ec4899', 'light-pink' => '#f9a8d4', 'dark-chocolate' => '#3e2723',
        'white' => '#ffffff', 'sand' => '#d4c5a9', 'gold' => '#f59e0b',
        'orange' => '#f97316', 'safety-green' => '#84cc16', 'lime' => '#a3e635',
        'indigo-blue' => '#4f46e5', 'irish-green' => '#16a34a',
        'ceramic-pink' => '#f9a8d4', 'ceramic-green' => '#22c55e', 'ceramic-yellow' => '#eab308',
        'ceramic-black' => '#1a1a2e', 'ceramic-red' => '#ef4444', 'ceramic-blue' => '#3b82f6',
    ];

    private const COLOR_LABELS_FR = [
        'black' => 'Noir', 'navy' => 'Bleu marine', 'forest-green' => 'Vert forêt',
        'dark-heather' => 'Gris', 'red' => 'Rouge', 'cherry-red' => 'Rouge cerise',
        'cardinal-red' => 'Rouge cardinal', 'garnet' => 'Grenat', 'maroon' => 'Bordeaux',
        'royal' => 'Bleu royal', 'purple' => 'Violet', 'heliconia' => 'Rose vif',
        'dark-chocolate' => 'Chocolat', 'white' => 'Blanc', 'light-pink' => 'Rose',
        'ceramic-pink' => 'Rose', 'ceramic-green' => 'Vert', 'ceramic-yellow' => 'Jaune',
        'ceramic-black' => 'Noir', 'ceramic-red' => 'Rouge', 'ceramic-blue' => 'Bleu',
    ];

    public function handle(): int
    {
        $storeId = config('shop.gelato.store_id');
        $apiKey = config('shop.gelato.api_key');

        if (! $storeId || ! $apiKey) {
            $this->error('GELATO_STORE_ID ou GELATO_API_KEY manquant dans .env');
            return self::FAILURE;
        }

        $this->info('Récupération des produits du store Gelato...');

        $response = Http::withHeaders(['X-API-KEY' => $apiKey])
            ->get("https://ecommerce.gelatoapis.com/v1/stores/{$storeId}/products");

        if (! $response->successful()) {
            $this->error('Erreur API Gelato : '.$response->status());
            return self::FAILURE;
        }

        $gelatoProducts = $response->json('products', []);
        $this->info(count($gelatoProducts).' produits trouvés sur Gelato.');

        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        foreach ($gelatoProducts as $gp) {
            $detail = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->get("https://ecommerce.gelatoapis.com/v1/stores/{$storeId}/products/{$gp['id']}")
                ->json();

            $result = $this->syncProduct($detail);
            $stats[$result]++;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Créés', (string) $stats['created']);
        $this->components->twoColumnDetail('Mis à jour', (string) $stats['updated']);
        $this->components->twoColumnDetail('Inchangés', (string) $stats['unchanged']);

        return self::SUCCESS;
    }

    private function syncProduct(array $gelato): string
    {
        $gelatoId = $gelato['id'];
        $title = $gelato['title'] ?? 'Sans titre';
        $variants = $gelato['variants'] ?? [];

        // Extraire couleurs et tailles uniques + construire le mapping
        $colorGroups = [];
        $storeVariantMap = [];

        foreach ($variants as $v) {
            $parts = explode(' - ', $v['title'] ?? '');
            $colorName = Str::slug($parts[0] ?? 'default');
            $size = $parts[1] ?? null;
            $uid = $v['productUid'] ?? '';
            $svid = $v['id'] ?? '';

            $storeVariantMap[$uid] = $svid;

            if (! isset($colorGroups[$colorName])) {
                $colorGroups[$colorName] = ['sizes' => [], 'uid_m' => null];
            }
            $colorGroups[$colorName]['sizes'][] = $size;

            // Garder le UID de la taille M (ou la première)
            if ($size === 'M' || ! $colorGroups[$colorName]['uid_m']) {
                $colorGroups[$colorName]['uid_m'] = $uid;
            }
        }

        // Chercher le produit existant
        $product = Product::all()->first(function ($p) use ($gelatoId) {
            return ($p->metadata['gelato_store_product_id'] ?? null) === $gelatoId;
        });

        if ($product) {
            return $this->updateExisting($product, $gelato, $colorGroups, $storeVariantMap);
        }

        return $this->createNew($gelato, $colorGroups, $storeVariantMap);
    }

    private function updateExisting(Product $product, array $gelato, array $colorGroups, array $storeVariantMap): string
    {
        $existingVariants = collect($product->variants ?? []);
        $newVariants = [];

        foreach ($colorGroups as $colorSlug => $group) {
            $existing = $existingVariants->first(fn ($v) => Str::slug($v['label'] ?? '') === $colorSlug
                || Str::contains($v['gelato_uid'] ?? '', $colorSlug));

            $newVariants[] = [
                'label' => $existing['label'] ?? (self::COLOR_LABELS_FR[$colorSlug] ?? ucfirst(str_replace('-', ' ', $colorSlug))),
                'color' => $existing['color'] ?? (self::COLOR_HEX[$colorSlug] ?? '#6b7280'),
                'gelato_uid' => $group['uid_m'],
                'images' => $existing['images'] ?? [],
            ];
        }

        // Extraire les tailles depuis la première couleur
        $sizes = collect($colorGroups)->first()['sizes'] ?? [];
        $sizes = array_values(array_unique(array_filter($sizes)));
        usort($sizes, fn ($a, $b) => $this->sizeOrder($a) <=> $this->sizeOrder($b));

        $metadata = $product->metadata;
        $metadata['store_variant_map'] = $storeVariantMap;
        $metadata['gelato_store_product_id'] = $gelato['id'];

        $hasChanged = $this->option('force')
            || count($newVariants) !== count($product->variants ?? [])
            || count($storeVariantMap) !== count($metadata['store_variant_map'] ?? []);

        if (! $hasChanged) {
            $this->components->twoColumnDetail("[INCHANGÉ] {$product->name}", count($newVariants).' variantes');
            return 'unchanged';
        }

        if ($this->option('dry-run')) {
            $this->components->twoColumnDetail("[DRY RUN] {$product->name}", count($newVariants).' variantes, '.count($storeVariantMap).' store mappings');
            return 'updated';
        }

        $updateData = [
            'variants' => $newVariants,
            'metadata' => array_merge($metadata, ['sizes' => $sizes]),
        ];

        // Ne jamais écraser les images existantes du produit
        // Les images sont gérées manuellement via l'admin
        if (empty($product->images)) {
            $previewUrl = $gelato['previewUrl'] ?? null;
            if ($previewUrl) {
                $updateData['images'] = [$previewUrl];
            }
        }

        $product->update($updateData);

        $this->components->twoColumnDetail("[MIS À JOUR] {$product->name}", count($newVariants).' couleurs, '.count($storeVariantMap).' mappings');
        return 'updated';
    }

    private function createNew(array $gelato, array $colorGroups, array $storeVariantMap): string
    {
        $variants = [];
        foreach ($colorGroups as $colorSlug => $group) {
            $variants[] = [
                'label' => self::COLOR_LABELS_FR[$colorSlug] ?? ucfirst(str_replace('-', ' ', $colorSlug)),
                'color' => self::COLOR_HEX[$colorSlug] ?? '#6b7280',
                'gelato_uid' => $group['uid_m'],
                'images' => [],
            ];
        }

        $sizes = collect($colorGroups)->first()['sizes'] ?? [];
        $sizes = array_values(array_unique(array_filter($sizes)));
        usort($sizes, fn ($a, $b) => $this->sizeOrder($a) <=> $this->sizeOrder($b));

        // Calculer le prix automatiquement via l'API Gelato + smartPrice
        $firstUid = collect($colorGroups)->first()['uid_m'] ?? '';
        $costBase = $this->fetchCostBase($firstUid);
        $category = $this->detectCategory($gelato['title'] ?? '');
        $price = ($costBase !== null) ? Product::smartPrice($costBase, $category) : 0;

        if ($this->option('dry-run')) {
            $this->components->twoColumnDetail("[DRY RUN CRÉÉ] {$gelato['title']}", count($variants)." couleurs, {$category}, coût {$costBase} USD → {$price} CAD");
            return 'created';
        }

        Product::create([
            'name' => $gelato['title'] ?? 'Produit Gelato',
            'slug' => Str::slug($gelato['title'] ?? 'produit-gelato'),
            'description' => strip_tags($gelato['description'] ?? ''),
            'price' => $price,
            'category' => $category,
            'images' => [],
            'variants' => $variants,
            'status' => 'draft',
            'metadata' => [
                'gelato_store_product_id' => $gelato['id'],
                'store_variant_map' => $storeVariantMap,
                'sizes' => $sizes,
                'cost_base' => $costBase,
                'cost_currency' => 'USD',
            ],
        ]);

        $this->components->twoColumnDetail("[CRÉÉ] {$gelato['title']}", count($variants)." couleurs, {$price} CAD (status: draft)");
        return 'created';
    }

    private function fetchCostBase(string $productUid): ?float
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => config('shop.gelato.api_key'),
            ])->get("https://product.gelatoapis.com/v3/products/{$productUid}/prices", [
                'country' => 'CA',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data[0]['price']) ? round((float) $data[0]['price'], 2) : null;
            }
        } catch (\Exception $e) {
            $this->components->warn("Erreur API prix : {$e->getMessage()}");
        }

        return null;
    }

    private function detectCategory(string $title): string
    {
        $title = strtolower($title);
        $map = [
            'hoodie' => 'hoodies', 't-shirt' => 't-shirts', 'tshirt' => 't-shirts',
            'mug' => 'mugs', 'tasse' => 'mugs', 'water bottle' => 'water-bottles',
            'bouteille' => 'water-bottles', 'tote' => 'tote-bags', 'sac' => 'tote-bags',
            'poster' => 'posters',
        ];

        foreach ($map as $keyword => $category) {
            if (str_contains($title, $keyword)) {
                return $category;
            }
        }

        return 'default';
    }

    private function sizeOrder(string $size): int
    {
        return match (strtoupper($size)) {
            'XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5,
            '2XL' => 6, '3XL' => 7, '4XL' => 8, '5XL' => 9,
            default => 10,
        };
    }
}
