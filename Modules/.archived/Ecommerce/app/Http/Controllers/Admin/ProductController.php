<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        }

        if ($category = $request->input('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('ecommerce_categories.id', $category));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->latest()->paginate(15);

        return view('ecommerce::admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::ordered()->get();

        return view('ecommerce::admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('ecommerce_products', 'slug')],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('ecommerce_products', 'sku')],
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'categories' => 'array',
            'categories.*' => 'exists:ecommerce_categories,id',
            'featured_image' => 'nullable|image|max:5120',
            'gallery' => 'nullable|array|max:20',
            'gallery.*' => 'image|max:5120',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        $product = Product::create($validated);
        $product->categories()->sync($request->input('categories', []));

        if ($request->hasFile('featured_image')) {
            $product->addMediaFromRequest('featured_image')->toMediaCollection('featured_image');
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $product->addMedia($file)->toMediaCollection('gallery');
            }
        }

        session()->flash('success', 'Produit créé avec succès.');

        return redirect()->route('admin.ecommerce.products.index');
    }

    public function edit(Product $product): View
    {
        $categories = Category::ordered()->get();

        return view('ecommerce::admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('ecommerce_products', 'slug')->ignore($product->id)],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('ecommerce_products', 'sku')->ignore($product->id)],
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'categories' => 'array',
            'categories.*' => 'exists:ecommerce_categories,id',
            'featured_image' => 'nullable|image|max:5120',
            'gallery' => 'nullable|array|max:20',
            'gallery.*' => 'image|max:5120',
            'remove_gallery' => 'nullable|array',
            'remove_gallery.*' => 'integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        $product->update($validated);
        $product->categories()->sync($request->input('categories', []));

        if ($request->filled('remove_gallery')) {
            $product->media()
                ->whereIn('id', $request->input('remove_gallery'))
                ->get()
                ->each->delete();
        }

        if ($request->hasFile('featured_image')) {
            $product->addMediaFromRequest('featured_image')->toMediaCollection('featured_image');
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $product->addMedia($file)->toMediaCollection('gallery');
            }
        }

        session()->flash('success', 'Produit mis à jour avec succès.');

        return redirect()->route('admin.ecommerce.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        session()->flash('success', 'Produit supprimé avec succès.');

        return redirect()->route('admin.ecommerce.products.index');
    }
}
