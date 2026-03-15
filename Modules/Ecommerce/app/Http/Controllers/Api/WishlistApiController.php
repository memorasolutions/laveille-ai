<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Controllers\BaseApiController;
use Modules\Ecommerce\Models\Wishlist;

class WishlistApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->with('product')
            ->paginate(20);

        return $this->respondSuccess($wishlists);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:ecommerce_products,id'],
        ]);

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
        ]);

        return $this->respondCreated($wishlist->load('product'));
    }

    public function destroy(Request $request, Wishlist $wishlist): JsonResponse
    {
        if ($wishlist->user_id !== $request->user()->id) {
            return $this->respondForbidden();
        }

        $wishlist->delete();

        return $this->respondSuccess(message: 'Article retiré des favoris.');
    }
}
