<?php

namespace Modules\Shop\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $validated = $this->validatePayload($request);
        $validated['metadata'] = $this->resolvePrintFile($request, $validated['metadata'] ?? []);

        $product = Product::create($validated);

        return redirect()->route('admin.shop.products.index')
            ->with('success', __('Produit créé avec succès.') . $this->designStatusMessage($product));
    }

    public function edit(Product $product)
    {
        return view('shop::admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validatePayload($request, $product);

        // Conserver les autres clés metadata existantes (store_variant_map, gelato_store_product_id...)
        $existingMeta = $product->metadata ?? [];
        $newMeta = array_merge($existingMeta, $validated['metadata'] ?? []);
        $validated['metadata'] = $this->resolvePrintFile($request, $newMeta);

        $product->update($validated);

        return redirect()->route('admin.shop.products.index')
            ->with('success', __('Produit mis à jour.') . $this->designStatusMessage($product->fresh()));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.shop.products.index')
            ->with('success', __('Produit archivé.'));
    }

    private function validatePayload(Request $request, ?Product $product = null): array
    {
        $slugRule = ['required', 'string', 'max:255'];
        $slugRule[] = $product
            ? Rule::unique('shop_products')->ignore($product->id)
            : 'unique:shop_products';

        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
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
            'metadata.print_file_url' => 'nullable|url|max:2048',
            'print_file_upload' => 'nullable|file|mimes:png,jpg,jpeg|max:10240',
        ]);
    }

    /**
     * #213 Fix #1 : si fichier upload fourni, le sauvegarder dans /public/images/shop/
     * et remplir metadata.print_file_url avec l'URL absolue. Sinon valider URL HEAD si présente.
     */
    private function resolvePrintFile(Request $request, array $metadata): array
    {
        if ($request->hasFile('print_file_upload')) {
            $file = $request->file('print_file_upload');
            $slug = Str::slug($request->input('slug', 'design-' . time()));
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = "design-{$slug}-" . time() . ".{$ext}";

            $destDir = public_path('images/shop');
            if (! is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $file->move($destDir, $filename);

            $metadata['print_file_url'] = url("/images/shop/{$filename}");
            $metadata['print_file_uploaded_at'] = now()->toIso8601String();
        }

        // Validation HEAD : si URL fournie, vérifier qu'elle répond 200
        if (! empty($metadata['print_file_url'])) {
            try {
                $head = Http::timeout(8)->head($metadata['print_file_url']);
                $metadata['print_file_last_check_status'] = $head->status();
                $metadata['print_file_last_check_at'] = now()->toIso8601String();
                if (! $head->successful()) {
                    Log::warning("print_file_url HEAD non-200 ({$head->status()}) : {$metadata['print_file_url']}");
                }
            } catch (\Throwable $e) {
                $metadata['print_file_last_check_status'] = 'error:' . mb_substr($e->getMessage(), 0, 100);
                Log::warning("print_file_url HEAD erreur : {$e->getMessage()}");
            }
        }

        return $metadata;
    }

    private function designStatusMessage(Product $product): string
    {
        $meta = $product->metadata ?? [];
        $hasStoreMap = ! empty($meta['store_variant_map']);
        $hasUrl = ! empty($meta['print_file_url']);
        $headStatus = $meta['print_file_last_check_status'] ?? null;

        if (! $hasStoreMap && ! $hasUrl) {
            return ' ' . __('ATTENTION : aucun design configure - les commandes seront REFUSEES par le guard.');
        }
        if ($hasUrl && $headStatus !== null && $headStatus !== 200 && ! is_int($headStatus)) {
            return ' ' . __('ATTENTION : print_file_url a echoue HEAD check (statut: ' . $headStatus . ').');
        }
        if ($hasUrl && is_int($headStatus) && $headStatus !== 200) {
            return ' ' . __('ATTENTION : print_file_url retourne ' . $headStatus . '.');
        }
        return '';
    }
}
