<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\Core\Services\ViewCounterService;
use Modules\Tools\Models\Tool;

class PublicToolController extends Controller
{
    public function index(): Response
    {
        // #190 : tri par defaut popularite (views_count desc) puis sort_order asc
        $tools = Tool::active()
            ->orderByDesc('views_count')
            ->orderBy('sort_order')
            ->get();

        // Categories disponibles (distinct non-null) avec counts
        $categories = $tools->whereNotNull('category')->groupBy('category')
            ->map(fn ($group) => $group->count())->toArray();

        // #219 : force revalidation navigateur (filtres Alpine pouvaient rester invisibles via cache stale)
        return response()
            ->view('tools::public.index', compact('tools', 'categories'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function show(string $slug): View
    {
        $tool = Tool::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // #313 P0.2 : placeholder admin-only si is_under_construction (vue tools::public.under-construction)
        // Round 21 (2026-07-27) : réutilise Tool::isAccessibleTo() au lieu de dupliquer la logique.
        // Round 22 : $tool passé en 3e argument - $tool est déjà chargé ci-dessus, évite une
        // requête Tool redondante à chaque chargement de page (page la plus visitée du site).
        if (! Tool::isAccessibleTo($slug, request()->user(), $tool)) {
            return view('tools::public.under-construction', compact('tool'));
        }

        // #190 - incident 2026-08-13 : increment views_count délégué au service partagé
        // (filtre robots réel + déduplication rapprochée, jamais de casse de page).
        ViewCounterService::record($tool, 'views_count');

        $this->trackUsage($slug);

        $viewName = "tools::public.tools.{$slug}";

        if (! view()->exists($viewName)) {
            $viewName = 'tools::public.show';
        }

        $data = compact('tool');

        // Charger le fichier JSON de config si disponible pour cet outil
        $jsonPath = module_path('Tools', "resources/data/{$slug}.json");
        if (file_exists($jsonPath)) {
            $data['toolConfig'] = json_decode(file_get_contents($jsonPath), true);
        }

        return view($viewName, $data);
    }

    private function trackUsage(string $slug): void
    {
        if (request()->isMethod('HEAD') || ! Schema::hasTable('public_tool_usages')) {
            return;
        }
        try {
            DB::statement(
                'INSERT INTO public_tool_usages (slug, day, count, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE count = count + 1, updated_at = NOW()',
                [$slug, now()->toDateString()]
            );
        } catch (\Throwable $e) {}
    }
}
