<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shop\Services\CartService;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index()
    {
        return view('shop::public.cart', [
            'content' => $this->cartService->getContent(),
            'subtotal' => $this->cartService->getSubtotal(),
            'tax' => $this->cartService->getTaxAmount(),
            'total' => $this->cartService->getTotal(),
            'itemCount' => $this->cartService->itemCount(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'quantity' => 'integer|min:1',
            'variant_label' => 'nullable|string',
            'variant_gelato_uid' => 'nullable|string',
            'size_label' => 'nullable|string',
        ]);

        $variantLabel = $request->input('variant_label');
        if ($request->filled('size_label') && $variantLabel) {
            $variantLabel = $variantLabel . ' - ' . $request->input('size_label');
        } elseif ($request->filled('size_label')) {
            $variantLabel = $request->input('size_label');
        }

        $this->cartService->add(
            $request->integer('product_id'),
            $request->integer('quantity', 1),
            $variantLabel,
            $request->input('variant_gelato_uid')
        );

        $product = \Modules\Shop\Models\Product::find($request->integer('product_id'));
        return back()->with('success', __('Produit ajouté au panier.'))->with('cart_added', [
            'name' => $product?->name ?? __('Produit'),
            'variant' => $request->input('variant_label'),
            'price' => $product?->price ?? 0,
            'image' => $product?->images[0] ?? null,
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:shop_products,id']);

        $this->cartService->remove(
            $request->integer('product_id'),
            $request->input('variant_label')
        );

        if ($request->wantsJson()) {
            return response()->json($this->cartTotals());
        }

        return back()->with('success', __('Produit retiré du panier.'));
    }

    public function updateQuantity(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $this->cartService->updateQuantity(
            $request->integer('product_id'),
            $request->integer('quantity'),
            $request->input('variant_label')
        );

        if ($request->wantsJson()) {
            return response()->json($this->cartTotals());
        }

        return back()->with('success', __('Quantité mise à jour.'));
    }

    public function updateVariant(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'old_variant_label' => 'required|string',
            'new_variant_label' => 'required|string',
            'new_gelato_uid' => 'nullable|string',
        ]);

        $this->cartService->updateItemVariant(
            $request->integer('product_id'),
            $request->input('old_variant_label'),
            $request->input('new_variant_label'),
            $request->input('new_gelato_uid')
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'new_variant_label' => $request->input('new_variant_label')]);
        }

        return back()->with('success', __('Option mise à jour.'));
    }

    private function cartTotals(): array
    {
        $subtotal = $this->cartService->getSubtotal();
        $tps = round($subtotal * config('shop.tax.tps', 5) / 100, 2);
        $tvq = round($subtotal * config('shop.tax.tvq', 9.975) / 100, 2);

        return [
            'success' => true,
            'subtotal' => $subtotal,
            'tps' => $tps,
            'tvq' => $tvq,
            'total' => round($subtotal + $tps + $tvq, 2),
            'itemCount' => $this->cartService->itemCount(),
        ];
    }
}
