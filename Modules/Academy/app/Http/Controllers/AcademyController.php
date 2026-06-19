<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Course;
use Throwable;

class AcademyController extends Controller
{
    public function index(Request $request)
    {
        $q             = trim((string) $request->input('q', ''));
        $currentFilter = $request->input('filter');
        $currentLevel  = $request->input('level');
        $currentSearch = $q;

        if ($q !== '') {
            // Recherche avec Scout (Meilisearch) — fallback SQL LIKE si indisponible/erreur
            try {
                $ids   = $this->searchWithScout($q);
                $query = Course::published()->whereIn('id', $ids->all());
            } catch (Throwable) {
                $query = $this->buildFallbackQuery($q);
            }
        } else {
            $query = Course::published();
        }

        // Filtres communs (accès + niveau)
        if ($currentFilter === 'free') {
            $query->where('access_type', 'free');
        } elseif ($currentFilter === 'paid') {
            $query->where('access_type', '!=', 'free');
        }

        if ($currentLevel) {
            $query->where('level', $currentLevel);
        }

        $courses = $query->orderBy('published_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('academy::public.index', compact('courses', 'currentFilter', 'currentLevel', 'currentSearch'));
    }

    /**
     * Recherche Scout : retourne une Collection d'IDs (peut lever une exception si Scout KO).
     *
     * @throws Throwable
     */
    private function searchWithScout(string $q): Collection
    {
        if (! method_exists(Course::class, 'search') || ! class_exists('\Laravel\Scout\EngineManager')) {
            throw new \RuntimeException('Scout non disponible.');
        }

        return Course::search($q)->keys();
    }

    /**
     * Fallback SQL LIKE sur title + summary (toujours disponible, pas de dépendance externe).
     */
    private function buildFallbackQuery(string $q): Builder
    {
        $like = '%' . addcslashes($q, '%_\\') . '%';

        return Course::published()->where(function (Builder $builder) use ($like) {
            $builder->where('title', 'LIKE', $like)
                ->orWhere('summary', 'LIKE', $like);
        });
    }
}
