<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Carbon\Carbon;

/**
 * Service de recherche DB pour les combobox du générateur de prompt newsletter.
 *
 * Chaque méthode retourne un tableau [{id, label, sublabel?}].
 * Les modèles sont chargés via class_exists() (modules désactivables).
 */
class PromptBuilderSearchService
{
    private const LIMIT = 30;

    /**
     * Point d'entrée unique — dispatche selon le type.
     *
     * @param  string      $type     news|tool|term|article|interactive_tool
     * @param  string      $q        texte libre
     * @param  string|null $dateFrom YYYY-MM-DD (news seulement)
     * @param  string|null $dateTo   YYYY-MM-DD (news seulement)
     * @param  string|null $company  texte (news seulement)
     * @return list<array{id:int,label:string,sublabel?:string}>
     */
    public function search(
        string $type,
        string $q = '',
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $company = null,
    ): array {
        return match ($type) {
            'news'             => $this->searchNews($q, $dateFrom, $dateTo, $company),
            'tool'             => $this->searchDirectoryTool($q),
            'term'             => $this->searchTerm($q),
            'article'          => $this->searchBlogArticle($q),
            'interactive_tool' => $this->searchInteractiveTool($q),
            default            => [],
        };
    }

    /**
     * @return list<array{id:int,label:string,sublabel:string}>
     */
    private function searchNews(
        string $q,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $company,
    ): array {
        /** @var class-string|false $modelClass */
        $modelClass = class_exists(\Modules\News\Models\NewsArticle::class)
            ? \Modules\News\Models\NewsArticle::class
            : false;

        if ($modelClass === false) {
            return [];
        }

        $query = $modelClass::query()->where('is_published', true);

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($q2) use ($like): void {
                $q2->where('title', 'like', $like)
                   ->orWhere('seo_title', 'like', $like)
                   ->orWhere('summary', 'like', $like);
            });
        }

        if ($dateFrom !== null) {
            $query->where('pub_date', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo !== null) {
            $query->where('pub_date', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        if ($company !== null && $company !== '') {
            $likeCompany = '%' . $company . '%';
            $query->where(function ($q2) use ($likeCompany): void {
                $q2->where('title', 'like', $likeCompany)
                   ->orWhere('summary', 'like', $likeCompany);
            });
        }

        $results = $query
            ->orderBy('pub_date', 'desc')
            ->limit(self::LIMIT)
            ->get(['id', 'title', 'seo_title', 'pub_date', 'news_source_id']);

        return $results->map(function ($article): array {
            $label    = (string) ($article->seo_title ?: $article->title);
            $dateStr  = $article->pub_date
                ? Carbon::parse($article->pub_date)->format('d M Y')
                : '';
            $sublabel = $dateStr;

            return [
                'id'       => (int) $article->id,
                'label'    => $label,
                'sublabel' => $sublabel,
            ];
        })->values()->all();
    }

    /**
     * @return list<array{id:int,label:string}>
     */
    private function searchDirectoryTool(string $q): array
    {
        /** @var class-string|false $modelClass */
        $modelClass = class_exists(\Modules\Directory\Models\Tool::class)
            ? \Modules\Directory\Models\Tool::class
            : false;

        if ($modelClass === false) {
            return [];
        }

        $query = $modelClass::query()->where('status', 'published');

        if ($q !== '') {
            $like = '%' . $q . '%';
            // Le champ name est translatable (JSON) — recherche groupée pour ne PAS
            // court-circuiter le filtre status='published' via la précédence du OR.
            $query->where(function ($q2) use ($like): void {
                $q2->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, \'$."fr_CA"\'))) LIKE LOWER(?)', [$like])
                   ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, \'$."fr"\'))) LIKE LOWER(?)', [$like])
                   ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, \'$."en"\'))) LIKE LOWER(?)', [$like]);
            });
        }

        $results = $query
            ->orderBy('id', 'desc')
            ->limit(self::LIMIT)
            ->get(['id', 'name']);

        return $results->map(function (\Illuminate\Database\Eloquent\Model $tool): array {
            // getTranslation peut ne pas exister si module différent — defensive
            $label = method_exists($tool, 'getTranslation')
                ? (string) ($tool->getTranslation('name', 'fr_CA', false) // @phpstan-ignore-line method.notFound
                    ?: $tool->getTranslation('name', 'fr', false)
                    ?: $tool->getTranslation('name', 'en', false)
                    ?: (string) $tool->getAttribute('name'))
                : (string) $tool->getAttribute('name');

            return ['id' => (int) $tool->getKey(), 'label' => $label];
        })->values()->all();
    }

    /**
     * @return list<array{id:int,label:string}>
     */
    private function searchTerm(string $q): array
    {
        /** @var class-string|false $modelClass */
        $modelClass = class_exists(\Modules\Dictionary\Models\Term::class)
            ? \Modules\Dictionary\Models\Term::class
            : false;

        if ($modelClass === false) {
            return [];
        }

        $query = $modelClass::query()->where('is_published', true);

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($q2) use ($like): void {
                $q2->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, \'$."fr_CA"\'))) LIKE LOWER(?)', [$like])
                   ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, \'$."fr"\'))) LIKE LOWER(?)', [$like])
                   ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, \'$."en"\'))) LIKE LOWER(?)', [$like]);
            });
        }

        $results = $query
            ->orderBy('id', 'desc')
            ->limit(self::LIMIT)
            ->get(['id', 'name']);

        return $results->map(function (\Illuminate\Database\Eloquent\Model $term): array {
            $label = method_exists($term, 'getTranslation')
                ? (string) ($term->getTranslation('name', 'fr_CA', false) // @phpstan-ignore-line method.notFound
                    ?: $term->getTranslation('name', 'fr', false)
                    ?: $term->getTranslation('name', 'en', false)
                    ?: (string) $term->getAttribute('name'))
                : (string) $term->getAttribute('name');

            return ['id' => (int) $term->getKey(), 'label' => $label];
        })->values()->all();
    }

    /**
     * @return list<array{id:int,label:string,sublabel:string}>
     */
    private function searchBlogArticle(string $q): array
    {
        /** @var class-string|false $modelClass */
        $modelClass = class_exists(\Modules\Blog\Models\Article::class)
            ? \Modules\Blog\Models\Article::class
            : false;

        if ($modelClass === false) {
            return [];
        }

        $query = $modelClass::published();

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($q2) use ($like): void {
                $q2->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, \'$."fr_CA"\'))) LIKE LOWER(?)', [$like])
                   ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, \'$."fr"\'))) LIKE LOWER(?)', [$like])
                   ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, \'$."en"\'))) LIKE LOWER(?)', [$like]);
            });
        }

        $results = $query
            ->orderBy('published_at', 'desc')
            ->limit(self::LIMIT)
            ->get(['id', 'title', 'published_at']);

        return $results->map(function (\Illuminate\Database\Eloquent\Model $article): array {
            $label = method_exists($article, 'getTranslation')
                ? (string) ($article->getTranslation('title', 'fr_CA', false) // @phpstan-ignore-line method.notFound
                    ?: $article->getTranslation('title', 'fr', false)
                    ?: $article->getTranslation('title', 'en', false)
                    ?: (string) $article->getAttribute('title'))
                : (string) $article->getAttribute('title');

            $publishedAt = $article->getAttribute('published_at');
            $sublabel = $publishedAt
                ? Carbon::parse($publishedAt)->format('d M Y')
                : '';

            return ['id' => (int) $article->getKey(), 'label' => $label, 'sublabel' => $sublabel];
        })->values()->all();
    }

    /**
     * @return list<array{id:int,label:string}>
     */
    private function searchInteractiveTool(string $q): array
    {
        /** @var class-string|false $modelClass */
        $modelClass = class_exists(\Modules\Tools\Models\Tool::class)
            ? \Modules\Tools\Models\Tool::class
            : false;

        if ($modelClass === false) {
            return [];
        }

        $query = $modelClass::query()->where('is_active', true);

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where('name', 'like', $like);
        }

        $results = $query
            ->orderBy('id', 'desc')
            ->limit(self::LIMIT)
            ->get(['id', 'name']);

        return $results->map(fn ($tool): array => [
            'id'    => (int) $tool->id,
            'label' => (string) $tool->name,
        ])->values()->all();
    }
}
