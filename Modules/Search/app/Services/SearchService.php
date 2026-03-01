<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Search\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;
use Modules\Settings\Models\Setting;

class SearchService
{
    /**
     * @param  array<class-string>  $models
     */
    public function search(string $query, array $models, int $perPage = 15): array
    {
        $results = [];

        foreach ($models as $model) {
            if (method_exists($model, 'search')) {
                $results[$model] = $model::search($query)->take($perPage)->get();
            }
        }

        return $results;
    }

    public function searchAdmin(string $query, string $type = 'all', int $limit = 10): array
    {
        $results = [
            'users' => collect(),
            'articles' => collect(),
            'pages' => collect(),
            'plans' => collect(),
            'categories' => collect(),
            'settings' => collect(),
        ];

        if ($type === 'all' || $type === 'users') {
            $results['users'] = User::search($query)->take($limit)->get();
        }

        if ($type === 'all' || $type === 'articles') {
            $results['articles'] = Article::search($query)
                ->query(fn ($q) => $q->where('status', 'published'))
                ->take($limit)
                ->get();
        }

        if ($type === 'all' || $type === 'pages') {
            $results['pages'] = StaticPage::search($query)->take($limit)->get();
        }

        if ($type === 'all' || $type === 'plans') {
            $results['plans'] = Plan::search($query)
                ->query(fn ($q) => $q->where('is_active', true))
                ->take($limit)
                ->get();
        }

        if ($type === 'all' || $type === 'categories') {
            $results['categories'] = Category::search($query)->take($limit)->get();
        }

        if ($type === 'all' || $type === 'settings') {
            $results['settings'] = Setting::search($query)->take($limit)->get();
        }

        return $results;
    }

    public function searchNavbar(string $query, int $limit = 3): array
    {
        return [
            'users' => User::search($query)->take($limit)->get(),
            'articles' => Article::search($query)->take($limit)->get(),
            'settings' => Setting::search($query)->take($limit)->get(),
        ];
    }

    public function searchFront(string $query, int $perPage = 10): array
    {
        $articles = Article::search($query)
            ->query(fn ($q) => $q->where('status', 'published'))
            ->paginate($perPage);

        $pages = StaticPage::search($query)
            ->query(fn ($q) => $q->where('status', 'published'))
            ->paginate($perPage);

        return [
            'articles' => $articles,
            'pages' => $pages,
            'total' => $articles->total() + $pages->total(),
        ];
    }

    public function searchModel(string $model, string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $model::search($query)->paginate($perPage);
    }

    /**
     * @return array<class-string>
     */
    public function getSearchableModels(): array
    {
        return config('search.models', []);
    }
}
