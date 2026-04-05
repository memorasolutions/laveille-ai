<?php

namespace Modules\Shop\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\GelatoWizardService;

class ProductWizardController extends Controller
{
    public function step1()
    {
        $response = GelatoWizardService::getCatalogs();
        $catalogs = $response['data'] ?? $response;

        return view('shop::admin.wizard.step1-catalog', compact('catalogs'));
    }

    public function step1Store(Request $request)
    {
        $request->validate(['catalog_uid' => 'required|string']);
        session(['shop_wizard.catalog_uid' => $request->catalog_uid]);

        return redirect()->route('admin.shop.wizard.step2');
    }

    public function step2()
    {
        $catalogUid = session('shop_wizard.catalog_uid');
        if (! $catalogUid) {
            return redirect()->route('admin.shop.wizard.step1');
        }

        $response = GelatoWizardService::searchProducts($catalogUid, [], 50);
        $products = $response['products'] ?? [];
        $attributes = $response['hits']['attributeHits'] ?? [];

        return view('shop::admin.wizard.step2-product', compact('products', 'attributes', 'catalogUid'));
    }

    public function step2Store(Request $request)
    {
        $request->validate(['product_uid' => 'required|string']);
        session(['shop_wizard.product_uid' => $request->product_uid]);

        return redirect()->route('admin.shop.wizard.step3');
    }

    public function step3()
    {
        $catalogUid = session('shop_wizard.catalog_uid');
        if (! $catalogUid) {
            return redirect()->route('admin.shop.wizard.step1');
        }

        $attributes = GelatoWizardService::getAvailableAttributes($catalogUid);
        $colors = isset($attributes['GarmentColor']) ? array_keys($attributes['GarmentColor']) : [];
        $sizes = isset($attributes['GarmentSize']) ? array_keys($attributes['GarmentSize']) : [];
        $mugSizes = isset($attributes['MugSize']) ? array_keys($attributes['MugSize']) : [];
        $bagColors = isset($attributes['BagColor']) ? array_keys($attributes['BagColor']) : [];

        return view('shop::admin.wizard.step3-options', compact('colors', 'sizes', 'mugSizes', 'bagColors', 'catalogUid'));
    }

    public function step3Store(Request $request)
    {
        $request->validate([
            'colors' => 'required|array|min:1',
            'sizes' => 'nullable|array',
        ]);

        session([
            'shop_wizard.colors' => $request->colors,
            'shop_wizard.sizes' => $request->sizes ?? [],
        ]);

        return redirect()->route('admin.shop.wizard.step4');
    }

    public function step4()
    {
        if (! session('shop_wizard.product_uid')) {
            return redirect()->route('admin.shop.wizard.step1');
        }

        $categories = array_keys(config('shop.pricing.margins', []));

        return view('shop::admin.wizard.step4-design', compact('categories'));
    }

    public function step4Store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'category' => 'required|string',
            'design' => 'required|image|max:10240',
        ]);

        $slug = Str::slug($request->name);
        $designFile = $request->file('design');
        $designName = 'design-' . $slug . '.' . $designFile->getClientOriginalExtension();
        $designFile->move(public_path('images/shop'), $designName);

        session([
            'shop_wizard.name' => $request->name,
            'shop_wizard.slug' => $slug,
            'shop_wizard.short_description' => $request->short_description,
            'shop_wizard.category' => $request->category,
            'shop_wizard.design_file' => $designName,
        ]);

        return redirect()->route('admin.shop.wizard.step5');
    }

    public function step5()
    {
        $wizard = session('shop_wizard');
        if (empty($wizard['product_uid'])) {
            return redirect()->route('admin.shop.wizard.step1');
        }

        $variants = GelatoWizardService::generateVariants(
            $wizard['product_uid'],
            $wizard['sizes'] ?? ['m'],
            $wizard['colors'] ?? ['black'],
            $wizard['category'] ?? 'default'
        );

        $designUrl = config('app.url') . '/images/shop/' . ($wizard['design_file'] ?? '');

        return view('shop::admin.wizard.step5-preview', compact('wizard', 'variants', 'designUrl'));
    }

    public function step5Store(Request $request)
    {
        $wizard = session('shop_wizard');
        if (empty($wizard['product_uid'])) {
            return redirect()->route('admin.shop.wizard.step1');
        }

        $variants = GelatoWizardService::generateVariants(
            $wizard['product_uid'],
            $wizard['sizes'] ?? ['m'],
            $wizard['colors'] ?? ['black'],
            $wizard['category'] ?? 'default'
        );

        $designUrl = config('app.url') . '/images/shop/' . ($wizard['design_file'] ?? '');

        Product::create([
            'name' => $wizard['name'],
            'slug' => $wizard['slug'],
            'gelato_product_id' => $wizard['product_uid'],
            'short_description' => $wizard['short_description'] ?? '',
            'category' => $wizard['category'],
            'price' => $variants[0]['price'] ?? 0,
            'currency' => 'CAD',
            'images' => [$designUrl],
            'variants' => $variants,
            'metadata' => [
                'cost_base' => $variants[0]['cost'] ?? 0,
                'cost_currency' => 'USD',
                'catalog_uid' => $wizard['catalog_uid'],
            ],
            'status' => 'draft',
            'sort_order' => Product::max('sort_order') + 1,
        ]);

        session()->forget('shop_wizard');

        return redirect()->route('admin.shop.products.index')
            ->with('success', 'Produit cree avec ' . count($variants) . ' variants.');
    }
}
