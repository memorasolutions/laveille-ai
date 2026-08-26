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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Services\SearchRegistry;
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

        if (($type === 'all' || $type === 'articles') && class_exists(\Modules\Blog\Models\Article::class) && \Nwidart\Modules\Facades\Module::find('Blog')?->isEnabled()) {
            $results['articles'] = \Modules\Blog\Models\Article::search($query)
                ->query(fn ($q) => $q->where('status', 'published'))
                ->take($limit)
                ->get();
        }

        if (($type === 'all' || $type === 'pages') && class_exists(\Modules\Pages\Models\StaticPage::class) && \Nwidart\Modules\Facades\Module::find('Pages')?->isEnabled()) {
            $results['pages'] = \Modules\Pages\Models\StaticPage::search($query)->take($limit)->get();
        }

        if (($type === 'all' || $type === 'plans') && class_exists(\Modules\SaaS\Models\Plan::class) && \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
            $results['plans'] = \Modules\SaaS\Models\Plan::search($query)
                ->query(fn ($q) => $q->where('is_active', true))
                ->take($limit)
                ->get();
        }

        if (($type === 'all' || $type === 'categories') && class_exists(\Modules\Blog\Models\Category::class) && \Nwidart\Modules\Facades\Module::find('Blog')?->isEnabled()) {
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
        $registry = app(SearchRegistry::class);
        $sections = [];
        $total = 0;

        foreach ($registry->all() as $modelClass) {
            try {
                /** @var class-string<\Illuminate\Database\Eloquent\Model&\Modules\Core\Contracts\Searchable> $modelClass */
                $modelInstance = new $modelClass();
                $table = $modelInstance->getTable();
                $fields = $modelClass::searchableFields();
                $sectionKey = $modelClass::searchSectionKey();

                $qb = $modelClass::query()->where(function (Builder $q) use ($fields, $query) {
                    foreach ($fields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$query}%");
                    }
                });

                if (method_exists($modelClass, 'scopePublished')) {
                    $qb->published();
                } elseif (Schema::hasColumn($table, 'is_published')) {
                    $qb->where('is_published', true);
                } elseif (Schema::hasColumn($table, 'status')) {
                    $qb->where('status', 'published');
                }

                $paginator = $qb->paginate($perPage, ['*'], $sectionKey . '_page');
                $count = $paginator->total();

                if ($count > 0) {
                    $sections[$sectionKey] = [
                        'key' => $sectionKey,
                        'label' => $modelClass::searchSectionLabel(),
                        'icon' => $modelClass::searchSectionIcon(),
                        'priority' => $modelClass::searchPriority(),
                        'paginator' => $paginator,
                        'count' => $count,
                    ];
                    $total += $count;
                }
            } catch (\Throwable $e) {
                Log::warning('[SearchService] section failed', [
                    'model' => $modelClass,
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        uasort($sections, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return ['sections' => $sections, 'total' => $total];
    }

    public function searchModel(string $model, string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $model::search($query)->paginate($perPage);
    }

    /**
     * @return array<class-string>
     */
    /**
     * Modeles dont le contenu ne doit jamais sortir de l'administration.
     * User expose nom + courriel via toSearchableArray() ; Setting peut porter de la configuration.
     */
    private const MODELES_SENSIBLES = [
        \App\Models\User::class,
        \Modules\Settings\Models\Setting::class,
    ];

    /**
     * Modeles recherchables AUTORISES pour un utilisateur donne.
     *
     * Faille corrigee le 2026-08-26 : l'API `GET /api/v1/search` n'est protegee que par
     * `auth:sanctum`, sans aucune permission. Tout visiteur pouvait s'inscrire librement,
     * s'emettre un jeton depuis son tableau de bord, puis obtenir le nom ET LE COURRIEL de
     * tous les comptes. C'est une communication de renseignements personnels (Loi 25).
     *
     * On ne peut PAS desindexer User : le back-office s'en sert legitimement
     * (searchAdmin, searchNavbar). C'est donc l'ACCES qui est filtre, jamais l'index.
     *
     * Fail-closed : au moindre doute sur les droits, on protege la donnee.
     */
    public function getSearchableModelsFor(?\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        $autorise = false;

        try {
            $autorise = $user !== null
                && method_exists($user, 'can')
                && $user->can('view_admin_panel');
        } catch (\Throwable $e) {
            $autorise = false;
        }

        if ($autorise) {
            return $this->getSearchableModels();
        }

        return array_values(array_filter(
            $this->getSearchableModels(),
            static fn (string $modele): bool => ! in_array($modele, self::MODELES_SENSIBLES, true),
        ));
    }

    public function getSearchableModels(): array
    {
        return config('search.models', []);
    }
}
