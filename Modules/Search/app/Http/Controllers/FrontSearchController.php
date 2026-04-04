<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Search\Http\Controllers;

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
}
