<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Search\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Search\Services\SearchService;

class FrontSearchController
{
    public function __construct(private readonly SearchService $searchService)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->input('q');
        $results = $this->searchService->searchFront($query, 10);

        return view('search::front.results', compact('query', 'results'));
    }

    public function paletteJson(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->input('q');
        $results = $this->searchService->searchFront($query, 6);

        $sections = [];
        $total = 0;

        foreach ($results['sections'] as $key => $section) {
            $items = [];

            foreach ($section['paginator']->items() as $model) {
                if (! method_exists($model, 'searchableResultTitle')) {
                    continue;
                }

                try {
                    $items[] = [
                        'title' => $model->searchableResultTitle(),
                        'excerpt' => mb_strimwidth($model->searchableResultExcerpt(), 0, 140, '…'),
                        'url' => $model->searchableResultUrl(),
                    ];
                } catch (\Throwable) {
                    continue;
                }
            }

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'key' => $key,
                'label' => $section['label'],
                'icon' => $section['icon'],
                'total' => $section['count'],
                'items' => $items,
            ];
            $total += count($items);
        }

        return response()->json([
            'query' => $query,
            'total' => $total,
            'sections' => $sections,
            'see_all_url' => route('search.index', ['q' => $query]),
        ])->header('Cache-Control', 'private, max-age=60');
    }
}
