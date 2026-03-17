<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Review;

class ReviewApiController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->approved()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $reviews]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        if (Review::where('user_id', $user->id)->where('product_id', $product->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('Vous avez déjà laissé un avis pour ce produit.'),
            ], 422);
        }

        $isVerified = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', 'paid'))
            ->whereHas('variant', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'is_verified_purchase' => $isVerified,
        ]);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => __('Avis soumis avec succès. Il sera visible après approbation.'),
        ], 201);
    }
}
