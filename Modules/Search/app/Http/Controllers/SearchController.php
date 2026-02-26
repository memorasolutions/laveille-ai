<?php

declare(strict_types=1);

namespace Modules\Search\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Search\Services\SearchService;

class SearchController
{
    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'model' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->string('q')->toString();
        $perPage = $request->integer('per_page', 15);
        $modelFilter = $request->input('model');

        if ($modelFilter) {
            $models = $this->searchService->getSearchableModels();
            $matchedModel = collect($models)->first(
                fn (string $model) => class_basename($model) === $modelFilter
            );

            if (! $matchedModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid model filter.',
                ], 422);
            }

            $results = $this->searchService->searchModel($matchedModel, $query, $perPage);

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        }

        $models = $this->searchService->getSearchableModels();
        $results = $this->searchService->search($query, $models, $perPage);

        $formatted = [];
        foreach ($results as $model => $items) {
            $formatted[class_basename($model)] = $items->map(fn ($item) => [
                'id' => $item->getKey(),
                'type' => class_basename($model),
                'title' => $item->toSearchableArray()[array_key_first($item->toSearchableArray())] ?? '',
            ])->values();
        }

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }
}
