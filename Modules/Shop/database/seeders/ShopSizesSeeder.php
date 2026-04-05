<?php

namespace Modules\Shop\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopSizesSeeder extends Seeder
{
    public function run(): void
    {
        $this->updateTShirtSizes('t-shirt-laveille-ai-noir');
        $this->updateTShirtSizes('t-shirt-je-prompt-donc-je-suis');
    }

    protected function updateTShirtSizes(string $slug): void
    {
        $product = DB::table('shop_products')->where('slug', $slug)->first();

        if (! $product) {
            $this->command->warn("Produit '{$slug}' introuvable.");
            return;
        }

        $sizes = ['s' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL', '2xl' => '2XL'];
        $variants = [];

        foreach ($sizes as $key => $label) {
            $gelatoUid = str_replace('_gsi_m_', '_gsi_' . $key . '_', $product->gelato_product_id);
            $variants[] = ['label' => $label, 'gelato_uid' => $gelatoUid];
        }

        DB::table('shop_products')->where('slug', $slug)->update([
            'variants' => json_encode($variants),
            'updated_at' => now(),
        ]);

        $this->command->info("Tailles ajoutees pour '{$slug}' (5 variants).");
    }
}
