<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolCollection;

/**
 * S90 #43 — API publique JSON lecture seule pour outils + collections.
 *
 * Cible : agents IA (ChatGPT/Claude/Perplexity/Gemini), intégrations
 * MCP servers, tableaux de bord externes, chercheurs.
 *
 * Endpoints :
 *   GET /api/v1/directory/tools                  — liste paginée (params : page, per_page, category, pricing, has_education_pricing, q)
 *   GET /api/v1/directory/tools/{slug}           — détail outil
 *   GET /api/v1/directory/collections            — liste collections publiques
 *   GET /api/v1/directory/collections/{slug}     — détail collection avec outils
 *
 * Sans auth pour V1 (lecture publique). Throttle 60 req/min.
 * CORS permissif lecture (Access-Control-Allow-Origin: *).
 */
class PublicToolsController
{
    public function tools(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 30)));
        $page = max(1, (int) $request->query('page', 1));

        $query = Tool::query()
            ->where('status', 'published')
            ->with(['categories:id,slug,name']);

        // Filtres
        if ($pricing = $request->query('pricing')) {
            $query->where('pricing', $pricing);
        }

        if ($request->boolean('has_education_pricing')) {
            $query->where('has_education_pricing', 1);
        }

        if ($categorySlug = $request->query('category')) {
            $query->whereHas('categories', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($q = $request->query('q')) {
            $query->where(function ($qq) use ($q) {
                $qq->where('name->fr_CA', 'like', "%$q%")
                   ->orWhere('name->fr', 'like', "%$q%")
                   ->orWhere('name->en', 'like', "%$q%")
                   ->orWhere('short_description->fr_CA', 'like', "%$q%");
            });
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->jsonResponse([
            'data' => $paginator->getCollection()->map(fn (Tool $t) => $this->toolToArray($t))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'self' => $request->fullUrl(),
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public function toolShow(string $slug): JsonResponse
    {
        $tool = Tool::query()
            ->where('status', 'published')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"')) = ?", [$slug])
            ->with(['categories:id,slug,name'])
            ->first();

        if (! $tool) {
            return $this->jsonResponse(['error' => 'Tool not found', 'slug' => $slug], 404);
        }

        return $this->jsonResponse(['data' => $this->toolToArray($tool, full: true)]);
    }

    public function collections(Request $request): JsonResponse
    {
        $collections = ToolCollection::query()
            ->where('is_public', 1)
            ->withCount('tools')
            ->orderBy('id')
            ->get();

        return $this->jsonResponse([
            'data' => $collections->map(fn (ToolCollection $c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->name,
                'description' => $c->description,
                'tools_count' => $c->tools_count,
                'url_html' => url("/collections/{$c->slug}"),
                'url_api' => url("/api/v1/directory/collections/{$c->slug}"),
            ])->all(),
            'meta' => ['total' => $collections->count()],
        ]);
    }

    public function collectionShow(string $slug): JsonResponse
    {
        $collection = ToolCollection::query()
            ->where('slug', $slug)
            ->where('is_public', 1)
            ->with(['tools' => fn ($q) => $q->where('status', 'published')])
            ->first();

        if (! $collection) {
            return $this->jsonResponse(['error' => 'Collection not found', 'slug' => $slug], 404);
        }

        return $this->jsonResponse([
            'data' => [
                'id' => $collection->id,
                'slug' => $collection->slug,
                'name' => $collection->name,
                'description' => $collection->description,
                'url_html' => url("/collections/{$collection->slug}"),
                'tools' => $collection->tools->map(fn (Tool $t) => $this->toolToArray($t))->all(),
                'tools_count' => $collection->tools->count(),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        return $this->jsonResponse([
            'name' => 'La veille — API publique',
            'version' => 'v1',
            'description' => "API JSON publique en lecture seule de l'annuaire d'outils IA / EdTech francophones de La veille (laveille.ai). Sans authentification pour V1, rate-limited à 60 requêtes par minute par IP.",
            'license' => 'CC BY 4.0 — Attribution requise (https://laveille.ai)',
            'author' => 'Stéphane Lapointe — Memora solutions',
            'contact' => 'info@memora.ca',
            'endpoints' => [
                'GET /api/v1/directory/tools' => 'Liste paginée des outils. Params : page, per_page (max 100), category, pricing, has_education_pricing, q',
                'GET /api/v1/directory/tools/{slug}' => 'Détail complet d\'un outil par slug',
                'GET /api/v1/directory/collections' => 'Liste des collections éditoriales publiques (top par tâche + stacks par persona)',
                'GET /api/v1/directory/collections/{slug}' => 'Détail d\'une collection avec tous ses outils',
            ],
            'rate_limit' => '60 requests / minute / IP',
            'cache' => 'Cache HTTP 5 minutes recommandé',
            'docs_html' => url('/api'),
            'site' => url('/'),
        ]);
    }

    private function toolToArray(Tool $tool, bool $full = false): array
    {
        $base = [
            'id' => $tool->id,
            'slug' => $tool->slug,
            'name' => $tool->name,
            'url' => $tool->url,
            'short_description' => $tool->short_description,
            'pricing' => $tool->pricing,
            'unique_value' => $tool->unique_value,
            'has_education_pricing' => (bool) $tool->has_education_pricing,
            'education_pricing_type' => $tool->education_pricing_type,
            'education_pricing_url' => $tool->education_pricing_url,
            'lifecycle_status' => $tool->lifecycle_status,
            'last_change_detected_at' => $tool->last_change_detected_at?->toIso8601String(),
            'last_change_type' => $tool->last_change_type,
            'last_change_note' => $tool->last_change_note,
            'education_last_checked_at' => $tool->education_last_checked_at?->toIso8601String(),
            'updated_at' => $tool->updated_at?->toIso8601String(),
            'categories' => $tool->categories->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name])->all(),
            'url_html' => url('/annuaire/' . $tool->slug),
            'url_api' => url('/api/v1/directory/tools/' . $tool->slug),
        ];

        if ($full) {
            $base['description'] = $tool->description;
            $base['screenshot'] = $tool->screenshot ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot)) : null;
            $base['website_type'] = $tool->website_type;
            $base['launch_year'] = $tool->launch_year;
            $base['underlying_model'] = $tool->underlying_model;
            $base['is_multimodal'] = (bool) $tool->is_multimodal;
            $base['output_types'] = $tool->output_types;
            $base['has_api_access'] = (bool) $tool->has_api_access;
            $base['clicks_count'] = (int) ($tool->clicks_count ?? 0);
        }

        return $base;
    }

    private function jsonResponse(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Cache-Control' => 'public, max-age=300',
            'X-API-Version' => 'v1',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
