<?php

namespace Modules\Shop\Http\ViewComposers;

use Illuminate\View\View;
use Modules\Shop\Services\CartService;

class CartComposer
{
    public function __construct(protected CartService $cartService) {}

    public function compose(View $view): void
    {
        try {
            $view->with([
                'cartItemCount' => $this->cartService->itemCount(),
                'cartItems' => $this->cartService->getContent(),
                'cartTotal' => $this->cartService->getSubtotal(),
            ]);
        } catch (\Throwable $e) {
            $view->with([
                'cartItemCount' => 0,
                'cartItems' => [],
                'cartTotal' => 0,
            ]);
        }
    }
}
