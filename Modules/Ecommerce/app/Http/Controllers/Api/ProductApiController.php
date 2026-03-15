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
            ->with(['variants' => fn ($q) => $q->where('is_active', true)]);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('ecommerce_categories.id', $request->input('category_id')));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        return $this->respondSuccess($query->paginate(15));
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        return $this->respondSuccess($product);
    }
}
