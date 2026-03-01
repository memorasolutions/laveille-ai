<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Search\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Search\Services\SearchService;

final class FrontSearchController
{
    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    public function __invoke(Request $request): View
    {
        $request->validate([
            'q' => 'nullable|string|min:2|max:100',
        ]);

        $q = $request->get('q', '');

        $results = [
            'articles' => collect(),
            'pages' => collect(),
            'total' => 0,
        ];

        if ($q !== '' && strlen($q) >= 2) {
            $results = $this->searchService->searchFront(
                $q,
                (int) config('search.front_per_page', 10)
            );
        }

        return view('fronttheme::themes.gosass.search.results', [
            'q' => $q,
            'articles' => $results['articles'],
            'pages' => $results['pages'],
            'total' => $results['total'],
        ]);
    }
}
