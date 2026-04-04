<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Search\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
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

        if (($type === 'all' || $type === 'articles') && class_exists(\Modules\Blog\Models\Article::class)) {
            $results['articles'] = \Modules\Blog\Models\Article::search($query)
                ->query(fn ($q) => $q->where('status', 'published'))
                ->take($limit)
                ->get();
        }

        if (($type === 'all' || $type === 'pages') && class_exists(\Modules\Pages\Models\StaticPage::class)) {
            $results['pages'] = \Modules\Pages\Models\StaticPage::search($query)->take($limit)->get();
        }

        if (($type === 'all' || $type === 'plans') && class_exists(\Modules\SaaS\Models\Plan::class)) {
            $results['plans'] = \Modules\SaaS\Models\Plan::search($query)
                ->query(fn ($q) => $q->where('is_active', true))
                ->take($limit)
                ->get();
        }

        if (($type === 'all' || $type === 'categories') && class_exists(\Modules\Blog\Models\Category::class)) {
            $results['categories'] = \Modules\Blog\Models\Category::search($query)->take($limit)->get();
        }

        if ($type === 'all' || $type === 'settings') {
            $results['settings'] = Setting::search($query)->take($limit)->get();
        }

        return $results;
    }

    public function searchNavbar(string $query, int $limit = 3): array
    {
        $results = [
            'users' => User::search($query)->take($limit)->get(),
            'settings' => Setting::search($query)->take($limit)->get(),
        ];

        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $results['articles'] = \Modules\Blog\Models\Article::search($query)->take($limit)->get();
        } else {
            $results['articles'] = collect();
        }

        return $results;
    }

    public function searchFront(string $query, int $perPage = 10): array
    {
        $results = [
            'articles' => new LengthAwarePaginator([], 0, $perPage),
            'news' => new LengthAwarePaginator([], 0, $perPage),
            'tools' => new LengthAwarePaginator([], 0, $perPage),
            'terms' => new LengthAwarePaginator([], 0, $perPage),
            'acronyms' => new LengthAwarePaginator([], 0, $perPage),
            'total' => 0,
        ];

        if (class_exists(\Modules\Blog\Models\Article::class)) {
            try {
                $articles = \Modules\Blog\Models\Article::query()
                    ->where(function (Builder $q) use ($query) {
                        $q->where('title', 'LIKE', "%{$query}%")
                            ->orWhere('content', 'LIKE', "%{$query}%")
                            ->orWhere('excerpt', 'LIKE', "%{$query}%");
                    })
                    ->where('status', 'published')
                    ->paginate($perPage);
                $results['articles'] = $articles;
                $results['total'] += $articles->total();
            } catch (\Illuminate\Database\QueryException) {}
        }

        if (class_exists(\Modules\News\Models\NewsArticle::class)) {
            try {
                $news = \Modules\News\Models\NewsArticle::query()
                    ->where(function (Builder $q) use ($query) {
                        $q->where('title', 'LIKE', "%{$query}%")
                            ->orWhere('seo_title', 'LIKE', "%{$query}%")
                            ->orWhere('summary', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->where('is_published', true)
                    ->paginate($perPage);
                $results['news'] = $news;
                $results['total'] += $news->total();
            } catch (\Illuminate\Database\QueryException) {}
        }

        if (class_exists(\Modules\Directory\Models\Tool::class)) {
            try {
                $tools = \Modules\Directory\Models\Tool::query()
                    ->where(function (Builder $q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('short_description', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->where('status', 'published')
                    ->paginate($perPage);
                $results['tools'] = $tools;
                $results['total'] += $tools->total();
            } catch (\Illuminate\Database\QueryException) {}
        }

        if (class_exists(\Modules\Dictionary\Models\Term::class)) {
            try {
                $terms = \Modules\Dictionary\Models\Term::query()
                    ->where(function (Builder $q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('definition', 'LIKE', "%{$query}%")
                            ->orWhere('analogy', 'LIKE', "%{$query}%");
                    })
                    ->paginate($perPage);
                $results['terms'] = $terms;
                $results['total'] += $terms->total();
            } catch (\Illuminate\Database\QueryException) {}
        }

        if (class_exists(\Modules\Acronyms\Models\Acronym::class)) {
            try {
                $acronyms = \Modules\Acronyms\Models\Acronym::query()
                    ->where(function (Builder $q) use ($query) {
                        $q->where('acronym', 'LIKE', "%{$query}%")
                            ->orWhere('full_name', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->paginate($perPage);
                $results['acronyms'] = $acronyms;
                $results['total'] += $acronyms->total();
            } catch (\Illuminate\Database\QueryException) {}
        }

        return $results;
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
