<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\Tools\Models\Tool;

class PublicToolController extends Controller
{
    public function index(): View
    {
        // #190 : tri par defaut popularite (views_count desc) puis sort_order asc
        $tools = Tool::active()
            ->orderByDesc('views_count')
            ->orderBy('sort_order')
            ->get();

        // Categories disponibles (distinct non-null) avec counts
        $categories = $tools->whereNotNull('category')->groupBy('category')
            ->map(fn ($group) => $group->count())->toArray();

        return view('tools::public.index', compact('tools', 'categories'));
    }

    public function show(string $slug): View
    {
        $tool = Tool::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // #190 : increment views_count (ignore HEAD + bots)
        if (! request()->isMethod('HEAD') && Schema::hasColumn('tools', 'views_count')) {
            try { Tool::where('id', $tool->id)->increment('views_count'); } catch (\Throwable $e) {}
        }

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
