<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\CartService;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index()
    {
        // Pré-remplir : profil sauvegardé → dernière commande → nom du profil
        $savedAddress = [];
        if (Auth::check()) {
            $user = Auth::user();

            // Priorité 1 : adresse sauvegardée dans le profil (consentement Loi 25)
            $savedAddress = $user->shipping_address ?? [];

            // Priorité 2 : dernière commande réussie
            if (empty($savedAddress)) {
                $lastOrder = Order::where('user_id', $user->id)
                    ->whereNotNull('shipping_address')
                    ->whereIn('status', ['paid', 'processing', 'fulfilled', 'shipped', 'delivered'])
                    ->latest()
                    ->first();
                $savedAddress = $lastOrder?->shipping_address ?? [];
            }

            // Priorité 3 : nom depuis le profil
            if (empty($savedAddress['first_name'])) {
                $parts = explode(' ', $user->name ?? '', 2);
                $savedAddress['first_name'] = $parts[0] ?? '';
                $savedAddress['last_name'] = $parts[1] ?? '';
            }
        }

        return view('shop::public.cart', [
            'content' => $this->cartService->getContent(),
            'subtotal' => $this->cartService->getSubtotal(),
            'tax' => $this->cartService->getTaxAmount(),
            'total' => $this->cartService->getTotal(),
            'itemCount' => $this->cartService->itemCount(),
            'savedAddress' => $savedAddress,
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
        $gelatoUid = $request->input('variant_gelato_uid');

        if ($request->filled('size_label')) {
            $size = $request->input('size_label');
            // Combiner couleur + taille dans le label
            $variantLabel = $variantLabel ? $variantLabel . ' - ' . $size : $size;
            // Remplacer la taille dans le UID Gelato (_gsi_m_ → _gsi_2xl_, etc.)
            if ($gelatoUid) {
                $gelatoUid = preg_replace('/_gsi_[^_]+_/', '_gsi_' . strtolower($size) . '_', $gelatoUid);
            }
        }

        $this->cartService->add(
            $request->integer('product_id'),
            $request->integer('quantity', 1),
            $variantLabel,
            $gelatoUid
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

    private function cartTotals(?string $country = null, ?string $province = null): array
    {
        $subtotal = $this->cartService->getSubtotal();
        $country = $country ?? request()->input('country', 'CA');
        $province = $province ?? request()->input('province', 'QC');

        $tps = ($country === 'CA') ? round($subtotal * config('shop.tax.tps', 5) / 100, 2) : 0;
        $tvq = ($country === 'CA' && strtoupper($province) === 'QC') ? round($subtotal * config('shop.tax.tvq', 9.975) / 100, 2) : 0;

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
