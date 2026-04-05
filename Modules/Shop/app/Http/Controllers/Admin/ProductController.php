<?php

namespace Modules\Shop\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Shop\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withTrashed()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('shop::admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('shop::admin.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:shop_products',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'variants' => 'nullable|array',
            'category' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['published', 'draft', 'archived'])],
            'gelato_product_id' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        Product::create($validated);

        return redirect()->route('admin.shop.products.index')
            ->with('success', __('Produit créé avec succès.'));
    }

    public function edit(Product $product)
    {
        return view('shop::admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('shop_products')->ignore($product->id)],
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'variants' => 'nullable|array',
            'category' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['published', 'draft', 'archived'])],
            'gelato_product_id' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        $product->update($validated);

        return redirect()->route('admin.shop.products.index')
            ->with('success', __('Produit mis à jour.'));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.shop.products.index')
            ->with('success', __('Produit archivé.'));
    }
}
