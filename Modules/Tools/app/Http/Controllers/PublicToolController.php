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

    public function show(string $slug): View|Response
    {
        $tool = Tool::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // #313 P0.2 : placeholder admin-only si is_under_construction (vue tools::public.under-construction)
        // Round 21 (2026-07-27) : réutilise Tool::isAccessibleTo() au lieu de dupliquer la logique.
        // Round 22 : $tool passé en 3e argument - $tool est déjà chargé ci-dessus, évite une
        // requête Tool redondante à chaque chargement de page (page la plus visitée du site).
        if (! Tool::isAccessibleTo($slug, request()->user(), $tool)) {
            // Mode "maintenance" (2026-09-19) : contrairement aux modes historiques
            // construction/revision (200 + noindex, page qui ne sera jamais indexée), ce mode
            // ferme TEMPORAIREMENT un outil déjà public - 503 + Retry-After préserve son
            // classement pendant les travaux, jamais de noindex.
            if ($tool->construction_mode === 'maintenance') {
                $suggestions = Tool::active()
                    ->where('slug', '!=', $slug)
                    ->where('is_under_construction', false)
                    ->inRandomOrder()
                    ->limit(3)
                    ->get(['name', 'slug', 'icon']);

                // Mesuré en direct le 2026-09-19 (coordinateur) sur /outils/constructeur-prompts :
                // <meta name="robots" content="noindex, nofollow"> était quand même servi malgré
                // le @unless('page_noindex') de under-construction.blade.php. Émetteur réel trouvé
                // AVANT de corriger (jamais supposé) : Modules/FrontTheme/resources/views/layouts/
                // master.blade.php, @if(config('app.noindex')) - une 1re branche, TOTALEMENT
                // indépendante de page_noindex, qui gagne toujours quand le site entier est flagué
                // non-indexable (ex. APP_NOINDEX=true en local/.env, absent de .env.production).
                // Le texte "noindex, nofollow" (jamais "noindex, follow...") le prouve : c'est
                // exactement cette branche, pas la nôtre. On neutralise donc à la SORTIE plutôt que
                // dans ce layout partagé (édité en parallèle par un autre agent en ce moment) :
                // quelle que soit la source, aucune balise <meta name="robots"> contenant "noindex"
                // ne doit survivre sur CETTE réponse 503 précise - remplacée par l'équivalent
                // indexable standard déjà utilisé ailleurs sur le site. Aucun autre mode ni aucune
                // autre page n'est touché : le drapeau global continue de s'appliquer partout ailleurs.
                $html = view('tools::public.under-construction', compact('tool', 'suggestions'))->render();
                $html = preg_replace(
                    '/<meta\s+name="robots"\s+content="[^"]*noindex[^"]*"\s*\/?>/i',
                    '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">',
                    $html
                ) ?? $html;

                $response = response($html, 503)
                    ->header('Retry-After', (string) Tool::maintenanceRetryAfterSeconds());
                $response->headers->remove('X-Robots-Tag');

                return $response;
            }

            return response()->view('tools::public.under-construction', compact('tool'));
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

        // Bandeau "mode travaux, visible par toi seul" (2026-09-19) : n'apparaît QUE quand la
        // page RÉELLE est servie pendant que l'outil est fermé au public (donc via un
        // contournement - superadmin ou jeton d'aperçu, seuls chemins qui mènent ici alors que
        // is_under_construction est vrai). Injecté après rendu plutôt que dans chaque gabarit
        // d'outil (dédié ou générique) : reste générique par slug sans toucher au layout
        // partagé par tout le site ni aux gabarits spécifiques d'un outil.
        if ($tool->is_under_construction && $tool->construction_mode === 'maintenance') {
            $html = view($viewName, $data)->render();
            $banner = view('tools::public.partials.maintenance-preview-banner')->render();
            $html = preg_replace('/<body([^>]*)>/i', '<body$1>'.$banner, $html, 1) ?? $html;

            return response($html);
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
