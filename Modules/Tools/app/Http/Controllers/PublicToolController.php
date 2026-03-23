<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Tools\Models\Tool;

class PublicToolController extends Controller
{
    public function index(): View
    {
        $tools = Tool::active()->ordered()->get();

        return view('tools::public.index', compact('tools'));
    }

    public function show(string $slug): View
    {
        $tool = Tool::where('slug', $slug)->where('is_active', true)->firstOrFail();

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
}
