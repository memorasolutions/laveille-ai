<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Controllers\BaseApiController;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ProductVariant;
use Modules\Ecommerce\Services\CartService;

class CartApiController extends BaseApiController
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $cart->load('items.variant.product');

        return $this->respondSuccess([
            'cart' => $cart,
            'total' => $this->cartService->getTotal($cart),
            'item_count' => $this->cartService->getItemCount($cart),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:ecommerce_product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->cartService->getOrCreateCart($request->user());
        $variant = ProductVariant::findOrFail($validated['variant_id']);
        $item = $this->cartService->addItem($cart, $variant, (int) $validated['quantity']);

        return $this->respondCreated($item->load('variant.product'));
    }

    public function updateItem(Request $request, CartItem $item): JsonResponse
    {
        if ($item->cart->user_id !== $request->user()->id) {
            return $this->respondForbidden();
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = $this->cartService->updateItemQuantity($item, (int) $validated['quantity']);

        return $this->respondSuccess($item->load('variant.product'));
    }

    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        if ($item->cart->user_id !== $request->user()->id) {
            return $this->respondForbidden();
        }

        $this->cartService->removeItem($item);

        return $this->respondSuccess(message: 'Article retiré du panier.');
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $this->cartService->clear($cart);

        return $this->respondSuccess(message: 'Panier vidé.');
    }
}
