<?php

namespace Modules\Shop\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Modules\Shop\Models\Product;

class ShopProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $products = [
            [
                'name' => 'T-shirt "La veille.ai" classique noir',
                'slug' => 't-shirt-laveille-ai-noir',
                'gelato_product_id' => 'apparel_product_gca_t-shirt_gsc_crewneck_gcu_unisex_gqa_classic_gsi_m_gco_black_gpr_0-4',
                'category' => 't-shirts',
                'price' => Product::smartPrice(11.65, 't-shirts'),
                'short_description' => 'T-shirt unisex noir 100% coton avec le logo La veille.ai',
                'status' => 'draft',
                'metadata' => json_encode(['cost_base' => 11.65, 'cost_currency' => 'USD']),
                'sort_order' => 1,
            ],
            [
                'name' => 'Tasse "Prompt du matin" 11oz blanche',
                'slug' => 'tasse-prompt-du-matin',
                'gelato_product_id' => 'mug_product_msz_11-oz_mmat_ceramic-white_cl_4-0',
                'category' => 'mugs',
                'price' => Product::smartPrice(6.03, 'mugs'),
                'short_description' => 'Tasse en ceramique 11oz — parce que chaque matin commence par un bon prompt',
                'status' => 'draft',
                'metadata' => json_encode(['cost_base' => 6.03, 'cost_currency' => 'USD']),
                'sort_order' => 2,
            ],
            [
                'name' => 'Sac fourre-tout "IA et cafe" noir',
                'slug' => 'sac-fourre-tout-ia-cafe',
                'gelato_product_id' => 'bag_product_bsc_tote-bag_bqa_clc_bsi_std-t_bco_black_bpr_0-4',
                'category' => 'tote-bags',
                'price' => Product::smartPrice(11.64, 'tote-bags'),
                'short_description' => 'Sac fourre-tout en coton noir — pour transporter vos idees et votre laptop',
                'status' => 'draft',
                'metadata' => json_encode(['cost_base' => 11.64, 'cost_currency' => 'USD']),
                'sort_order' => 3,
            ],
            [
                'name' => 'Tasse "404 Coffee Not Found" 11oz noire',
                'slug' => 'tasse-404-coffee-not-found',
                'gelato_product_id' => 'mug_product_msz_11-oz_mmat_ceramic-black_cl_4-0',
                'category' => 'mugs',
                'price' => Product::smartPrice(6.03, 'mugs'),
                'short_description' => 'Tasse noire 11oz — erreur 404, cafe introuvable',
                'status' => 'draft',
                'metadata' => json_encode(['cost_base' => 6.03, 'cost_currency' => 'USD']),
                'sort_order' => 4,
            ],
            [
                'name' => 'T-shirt "Je prompt donc je suis" blanc',
                'slug' => 't-shirt-je-prompt-donc-je-suis',
                'gelato_product_id' => 'apparel_product_gca_t-shirt_gsc_crewneck_gcu_unisex_gqa_classic_gsi_m_gco_white_gpr_0-4',
                'category' => 't-shirts',
                'price' => Product::smartPrice(11.65, 't-shirts'),
                'short_description' => 'T-shirt unisex blanc — la philosophie de l\'ere IA',
                'status' => 'draft',
                'metadata' => json_encode(['cost_base' => 11.65, 'cost_currency' => 'USD']),
                'sort_order' => 5,
            ],
            [
                'name' => 'Sac fourre-tout "Neural Network" naturel',
                'slug' => 'sac-neural-network',
                'gelato_product_id' => 'bag_product_bsc_tote-bag_bqa_clc_bsi_std-t_bco_natural_bpr_0-4',
                'category' => 'tote-bags',
                'price' => Product::smartPrice(11.64, 'tote-bags'),
                'short_description' => 'Sac en coton naturel — votre reseau neuronal portatif',
                'status' => 'draft',
                'metadata' => json_encode(['cost_base' => 11.64, 'cost_currency' => 'USD']),
                'sort_order' => 6,
            ],
        ];

        foreach ($products as $product) {
            DB::table('shop_products')->updateOrInsert(
                ['slug' => $product['slug']],
                array_merge($product, [
                    'currency' => 'CAD',
                    'images' => json_encode([]),
                    'variants' => json_encode([]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('6 produits Shop inseres avec succes.');
    }
}
