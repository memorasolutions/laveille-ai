<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductAttribute;
use Modules\Ecommerce\Models\ProductAttributeValue;
use Modules\Ecommerce\Models\ProductVariant;

class EcommerceDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedAttributes();
        $this->seedProducts();
        $this->seedCoupons();
    }

    private function seedCategories(): void
    {
        $categories = ['Vêtements', 'Électronique', 'Maison', 'Sports', 'Accessoires'];

        foreach ($categories as $i => $name) {
            Category::firstOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name, 'position' => $i, 'is_active' => true]
            );
        }
    }

    private function seedAttributes(): void
    {
        $taille = ProductAttribute::firstOrCreate(
            ['slug' => 'taille'],
            ['name' => 'Taille', 'type' => 'size', 'position' => 0]
        );

        foreach (['S', 'M', 'L', 'XL'] as $i => $value) {
            ProductAttributeValue::firstOrCreate(
                ['attribute_id' => $taille->id, 'value' => $value],
                ['label' => $value, 'position' => $i]
            );
        }

        $couleur = ProductAttribute::firstOrCreate(
            ['slug' => 'couleur'],
            ['name' => 'Couleur', 'type' => 'color', 'position' => 1]
        );

        $colors = ['Rouge' => '#FF0000', 'Bleu' => '#0000FF', 'Noir' => '#000000', 'Blanc' => '#FFFFFF'];
        $i = 0;
        foreach ($colors as $value => $code) {
            ProductAttributeValue::firstOrCreate(
                ['attribute_id' => $couleur->id, 'value' => $value],
                ['label' => $value, 'color_code' => $code, 'position' => $i++]
            );
        }
    }

    private function seedProducts(): void
    {
        $categories = Category::all();
        $products = [
            ['name' => 'T-shirt classique', 'price' => 29.99],
            ['name' => 'Jeans slim', 'price' => 79.99],
            ['name' => 'Écouteurs sans fil', 'price' => 149.99],
            ['name' => 'Lampe de bureau LED', 'price' => 49.99],
            ['name' => 'Ballon de soccer', 'price' => 34.99],
            ['name' => 'Montre sport', 'price' => 199.99],
            ['name' => 'Sac à dos', 'price' => 59.99],
            ['name' => 'Clavier mécanique', 'price' => 129.99],
            ['name' => 'Coussin décoratif', 'price' => 24.99],
            ['name' => 'Gourde isotherme', 'price' => 39.99],
        ];

        foreach ($products as $i => $data) {
            $slug = str($data['name'])->slug()->toString();
            $product = Product::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'description' => 'Description du produit '.$data['name'],
                    'is_active' => true,
                    'is_featured' => $i < 4,
                ]
            );

            if ($categories->isNotEmpty()) {
                $product->categories()->syncWithoutDetaching(
                    $categories->random(rand(1, 2))->pluck('id')
                );
            }

            for ($v = 0; $v < rand(2, 3); $v++) {
                $sku = strtoupper($slug.'-'.str_pad((string) ($v + 1), 3, '0', STR_PAD_LEFT));
                ProductVariant::firstOrCreate(
                    ['sku' => $sku],
                    [
                        'product_id' => $product->id,
                        'price' => $data['price'] + ($v * 5),
                        'stock' => rand(0, 50),
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedCoupons(): void
    {
        Coupon::firstOrCreate(
            ['code' => 'BIENVENUE10'],
            ['type' => 'percent', 'value' => 10, 'is_active' => true]
        );

        Coupon::firstOrCreate(
            ['code' => 'LIVRAISON'],
            ['type' => 'free_shipping', 'value' => 0, 'is_active' => true]
        );
    }
}
