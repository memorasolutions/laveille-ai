<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Search\Services\SearchService;
use Spatie\Permission\Models\Role;

class SearchController
{
    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => 'nullable|string|min:2|max:100',
            'type' => 'nullable|string|in:all,users,roles,articles,pages,plans,categories,settings',
        ]);

        $q = $validated['q'] ?? '';
        $type = $validated['type'] ?? 'all';

        $users = collect();
        $roles = collect();
        $articles = collect();
        $pages = collect();
        $plans = collect();
        $categories = collect();
        $settings = collect();

        if (! empty($q) && strlen($q) >= 2) {
            $searchResults = $this->searchService->searchAdmin($q, $type);

            $users = $searchResults['users'] ?? collect();
            $articles = $searchResults['articles'] ?? collect();
            $pages = $searchResults['pages'] ?? collect();
            $plans = $searchResults['plans'] ?? collect();
            $categories = $searchResults['categories'] ?? collect();
            $settings = $searchResults['settings'] ?? collect();

            if ($type === 'all' || $type === 'roles') {
                $roles = Role::where('name', 'like', "%{$q}%")->limit(10)->get();
            }
        }

        $totalCount = $users->count() + $roles->count() + $articles->count()
            + $pages->count() + $plans->count() + $categories->count()
            + $settings->count();

        return view('backoffice::search.index', compact(
            'users', 'roles', 'articles', 'pages', 'plans',
            'categories', 'settings', 'totalCount', 'q', 'type',
        ));
    }
}
