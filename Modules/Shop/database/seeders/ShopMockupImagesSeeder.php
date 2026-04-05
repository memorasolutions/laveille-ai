<?php

namespace Modules\Shop\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopMockupImagesSeeder extends Seeder
{
    public function run(): void
    {
        $productsImages = [
            't-shirt-laveille-ai-noir' => ['mockup-tshirt-laveille-noir.png', 'design-tshirt-laveille-noir.png'],
            'tasse-prompt-du-matin' => ['mockup-tasse-prompt-matin.png', 'design-tasse-prompt-matin.png'],
            'sac-fourre-tout-ia-cafe' => ['mockup-sac-ia-cafe.png', 'design-sac-ia-cafe.png'],
            'tasse-404-coffee-not-found' => ['mockup-tasse-404.png', 'design-tasse-404.png'],
            't-shirt-je-prompt-donc-je-suis' => ['mockup-tshirt-je-prompt.png', 'design-tshirt-je-prompt.png'],
            'sac-neural-network' => ['mockup-sac-neural-network.png', 'design-sac-neural-network.png'],
        ];

        $appUrl = config('app.url');

        foreach ($productsImages as $slug => $files) {
            $urls = array_map(fn($f) => $appUrl . '/images/shop/' . $f, $files);

            DB::table('shop_products')->where('slug', $slug)->update([
                'images' => json_encode($urls),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('6 produits mis a jour avec mockups + designs.');
    }
}
