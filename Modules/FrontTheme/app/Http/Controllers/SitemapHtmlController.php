<?php

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class SitemapHtmlController extends Controller
{
    public function index()
    {
        $sections = [
            ['title' => 'Blog', 'url' => '/blog', 'description' => "Articles editoriaux sur l'IA et la technologie"],
        ];

        if (class_exists(\Modules\News\Models\NewsArticle::class)) {
            $sections[] = ['title' => 'Actualites', 'url' => '/actualites', 'description' => 'Veille automatisee en temps reel'];
        }

        if (Module::has('Directory')) {
            $sections[] = ['title' => 'Repertoire', 'url' => '/annuaire', 'description' => "Repertoire de 75+ outils d'IA"];
        }

        if (Module::has('Dictionary')) {
            $sections[] = ['title' => 'Glossaire', 'url' => '/glossaire', 'description' => 'Definitions des termes IA'];
        }

        if (Module::has('Acronyms')) {
            $sections[] = ['title' => 'Acronymes', 'url' => '/acronymes-education', 'description' => "307 acronymes de l'education quebecoise"];
        }

        if (Module::has('Tools')) {
            $sections[] = ['title' => 'Outils', 'url' => '/outils', 'description' => 'Outils interactifs gratuits'];
        }

        $sections[] = ['title' => 'FAQ', 'url' => '/faq', 'description' => 'Questions frequentes'];
        $sections[] = ['title' => 'Ressources', 'url' => '/ressources', 'description' => 'Hub central de toutes les ressources'];
        $sections[] = ['title' => 'Contact', 'url' => '/contact', 'description' => 'Nous joindre'];
        $sections[] = ['title' => 'Flux RSS', 'url' => '/feed', 'description' => 'Flux RSS des articles'];

        return view('fronttheme::sitemap-html', compact('sections'));
    }
}
