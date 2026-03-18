<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Controllers\BaseApiController;
use Modules\Ecommerce\Models\Product;

class ProductApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['variants' => fn ($q) => $q->where('is_active', true), 'media']);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('ecommerce_categories.id', $request->input('category_id')));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', true);
        }

        $sortBy = $request->input('sort_by', 'newest');
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return $this->respondSuccess($query->paginate($perPage));
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories', 'variants' => fn ($q) => $q->where('is_active', true), 'media'])
            ->firstOrFail();

        return $this->respondSuccess($product);
    }

    public function related(Product $product): JsonResponse
    {
        return $this->respondSuccess([
            'cross_sells' => $product->crossSells()->where('is_active', true)->get(),
            'up_sells' => $product->upSells()->where('is_active', true)->get(),
        ]);
    }
}
