<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Shop\Models\Product;

class PublicShopController extends Controller
{
    public function index()
    {
        $products = Product::published()
            ->orderBy('sort_order')
            ->paginate(config('shop.pagination', 12));

        return view('shop::public.index', compact('products'));
    }

    public function show(Product $product)
    {
        $schema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->short_description ?? strip_tags($product->description ?? ''),
            'image' => $product->images[0] ?? null,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('shop.show', $product),
                'priceCurrency' => config('shop.currency', 'CAD'),
                'price' => $product->price,
                'availability' => 'https://schema.org/InStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => config('app.name'),
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return view('shop::public.show', compact('product', 'schema'));
    }
}
