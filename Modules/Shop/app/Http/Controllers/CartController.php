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
        ]);

        $this->cartService->add(
            $request->integer('product_id'),
            $request->integer('quantity', 1),
            $request->input('variant_label'),
            $request->input('variant_gelato_uid')
        );

        return back()->with('success', __('Produit ajouté au panier.'))->with('cart_added', true);
    }

    public function remove(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:shop_products,id']);

        $this->cartService->remove(
            $request->integer('product_id'),
            $request->input('variant_label')
        );

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

        return back()->with('success', __('Quantité mise à jour.'));
    }
}
