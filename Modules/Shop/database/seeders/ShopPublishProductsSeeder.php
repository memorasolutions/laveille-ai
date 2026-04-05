<?php

namespace Modules\Shop\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ShopPublishProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            't-shirt-laveille-ai-noir' => 'design-tshirt-laveille-noir.png',
            'tasse-prompt-du-matin' => 'design-tasse-prompt-matin.png',
            'sac-fourre-tout-ia-cafe' => 'design-sac-ia-cafe.png',
            'tasse-404-coffee-not-found' => 'design-tasse-404.png',
            't-shirt-je-prompt-donc-je-suis' => 'design-tshirt-je-prompt.png',
            'sac-neural-network' => 'design-sac-neural-network.png',
        ];

        $appUrl = config('app.url');

        foreach ($products as $slug => $file) {
            $imageUrl = $appUrl . '/images/shop/' . $file;
            DB::table('shop_products')->where('slug', $slug)->update([
                'images' => json_encode([$imageUrl]),
                'status' => 'published',
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->command->info('6 produits publies avec images.');
    }
}
