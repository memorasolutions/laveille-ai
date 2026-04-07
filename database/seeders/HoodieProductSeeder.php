<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shop\Models\Product;

class HoodieProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::updateOrCreate(
            ['slug' => 'hoodie-je-prompt-donc-je-suis'],
            [
                'name' => 'Hoodie "Je prompt donc je suis"',
                'description' => "Restez au chaud avec style grâce à ce hoodie Gildan 18500 unisex heavy blend. Le design « Je prompt donc je suis » avec le logo cerveau IA est imprimé en haute qualité. Tissu 50 % coton, 50 % polyester avec fil air jet pour un toucher plus doux et moins de boulochage. Capuche doublée avec cordon assorti, poche kangourou frontale, poignets et taille côtelés avec élasthanne. Du S au 5XL.",
                'short_description' => 'Hoodie Gildan 18500 heavy blend avec le design « Je prompt donc je suis ». 50/50 coton-polyester, S-5XL.',
                'price' => 39.99,
                'currency' => 'CAD',
                'category' => 'hoodies',
                'status' => 'published',
                'sort_order' => 3,
                'images' => [
                    '/images/shop/hoodie-prompt-noir_2.jpeg',
                    '/images/shop/hoodie-prompt-noir_1.jpeg',
                    '/images/shop/hoodie-prompt-noir_3.jpeg',
                ],
                'variants' => [
                    ['label' => 'Noir', 'color' => '#1a1a2e', 'gelato_uid' => 'apparel_product_gca_hoodie_gsc_pullover_gcu_unisex_gqa_classic_gsi_m_gco_black_gpr_4-4', 'images' => ['/images/shop/hoodie-prompt-noir_2.jpeg', '/images/shop/hoodie-prompt-noir_1.jpeg', '/images/shop/hoodie-prompt-noir_3.jpeg']],
                    ['label' => 'Bleu marine', 'color' => '#1e3a5f', 'gelato_uid' => 'apparel_product_gca_hoodie_gsc_pullover_gcu_unisex_gqa_classic_gsi_m_gco_navy_gpr_4-4', 'images' => ['/images/shop/hoodie-prompt-bleu_1.jpeg']],
                    ['label' => 'Vert forêt', 'color' => '#2d5a27', 'gelato_uid' => 'apparel_product_gca_hoodie_gsc_pullover_gcu_unisex_gqa_classic_gsi_m_gco_forest_green_gpr_4-4', 'images' => ['/images/shop/hoodie-prompt-forest_1.jpeg']],
                    ['label' => 'Gris', 'color' => '#9ca3af', 'gelato_uid' => 'apparel_product_gca_hoodie_gsc_pullover_gcu_unisex_gqa_classic_gsi_m_gco_sport_grey_gpr_4-4', 'images' => ['/images/shop/hoodie-prompt-gris_1.jpeg']],
                    ['label' => 'Rose', 'color' => '#f9a8d4', 'gelato_uid' => 'apparel_product_gca_hoodie_gsc_pullover_gcu_unisex_gqa_classic_gsi_m_gco_light_pink_gpr_4-4', 'images' => ['/images/shop/hoodie-prompt-rose_1.jpeg', '/images/shop/hoodie-prompt-rose_2.jpeg']],
                ],
                'metadata' => ['cost_base' => 26.16, 'sizes' => ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL']],
            ]
        );

        $this->command->info('Hoodie "Je prompt donc je suis" créé/mis à jour.');
    }
}
