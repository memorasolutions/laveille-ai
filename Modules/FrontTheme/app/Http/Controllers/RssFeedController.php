<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Nwidart\Modules\Facades\Module;

/**
 * S90 #43 — Flux RSS de La veille (Concentré IA hebdo + Nouveautés annuaire).
 *
 * Mode teaser : excerpt 200-300 mots + lien vers l'article complet.
 * Compatible avec lecteurs RSS humains (Feedly, Inoreader) et agents IA
 * (ChatGPT/Claude/Perplexity/Gemini RSS consumers).
 */
class RssFeedController extends Controller
{
    /**
     * Flux RSS des Concentrés IA hebdomadaires.
     */
    public function concentres(): Response
    {
        $articles = collect();
        $articleClass = 'Modules\\Blog\\Models\\Article';

        if (Module::has('Blog') && Module::find('Blog')?->isEnabled() && class_exists($articleClass)) {
            $articles = $articleClass::query()
                ->published()
                ->with(['user', 'blogCategory'])
                ->where(function ($q) {
                    // Filtre Concentré : titre commence par "Concentré IA" OU catégorie "le-concentre"
                    $q->where('title->fr_CA', 'like', '%Concentré%')
                        ->orWhere('title->fr', 'like', '%Concentré%')
                        ->orWhereHas('blogCategory', function ($qc) {
                            $qc->where('slug->fr_CA', 'le-concentre')
                               ->orWhere('slug->fr', 'le-concentre');
                        });
                })
                ->latest('published_at')
                ->take(20)
                ->get();
        }

        $xml = view('fronttheme::rss.concentres', [
            'articles' => $articles,
            'feedTitle' => 'La veille — Concentré IA hebdo',
            'feedDescription' => 'Le résumé hebdomadaire des nouveautés IA et techno au Québec, par Stéphane Lapointe (Memora). Publié chaque dimanche.',
            'feedUrl' => url('/rss/concentres.xml'),
            'siteUrl' => url('/'),
            'lastBuildDate' => now()->toRfc2822String(),
        ])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=1800'); // 30 min cache
    }

    /**
     * Flux RSS des nouveautés du Répertoire d'outils.
     */
    public function annuaire(): Response
    {
        $tools = collect();
        $toolClass = 'Modules\\Directory\\Models\\Tool';

        if (Module::has('Directory') && Module::find('Directory')?->isEnabled() && class_exists($toolClass)) {
            $tools = $toolClass::query()
                ->published()
                ->with('categories')
                ->latest('created_at')
                ->take(30)
                ->get();
        }

        $xml = view('fronttheme::rss.annuaire', [
            'tools' => $tools,
            'feedTitle' => 'La veille — Nouveautés répertoire d\'outils',
            'feedDescription' => 'Les derniers outils IA et techno ajoutés au répertoire de La veille (282+ outils francophones).',
            'feedUrl' => url('/rss/annuaire.xml'),
            'siteUrl' => url('/'),
            'lastBuildDate' => now()->toRfc2822String(),
        ])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=1800');
    }
}
