<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

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

        $ogImage = asset('images/og-image.png');
        if ($tool->featured_image && file_exists(public_path($tool->featured_image))) {
            $ogImage = asset($tool->featured_image).'?v='.filemtime(public_path($tool->featured_image));
        }

        $data = compact('tool', 'ogImage');

        // Charger le fichier JSON de config si disponible pour cet outil
        $jsonPath = module_path('Tools', "resources/data/{$slug}.json");
        if (file_exists($jsonPath)) {
            $data['toolConfig'] = json_decode(file_get_contents($jsonPath), true);
        }

        return view($viewName, $data);
    }
}
