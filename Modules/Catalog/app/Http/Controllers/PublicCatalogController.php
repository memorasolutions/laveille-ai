<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Catalog\Services\MoodleCatalogService;

class PublicCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $categories = app(MoodleCatalogService::class)->getCatalogue();

        // Pré-filtre par thème (?theme=<slug>) : utilisé par le méga-menu « Cours par thèmes »
        // de l'académie Moodle. Un slug inconnu ou absent retombe sur « all » (grille complète,
        // aucun filtre) - repli propre, jamais de casse.
        $theme = trim((string) $request->query('theme', ''));
        $slugs = array_column($categories, 'slug');
        $activeTheme = in_array($theme, $slugs, true) ? $theme : 'all';

        return view('catalog::public.index', compact('categories', 'activeTheme'));
    }
}
