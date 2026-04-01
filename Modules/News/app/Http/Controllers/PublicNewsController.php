<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\News\Models\NewsArticle;
use Modules\Settings\Facades\Settings;

class PublicNewsController extends Controller
{
    public function index(): View
    {
        $articles = NewsArticle::published()
            ->recent()
            ->with('source')
            ->take(50)
            ->get();

        // Grouper par catégorie pour le format digest
        $grouped = $articles->groupBy(function ($article) {
            return $article->category_tag ?: __('Général');
        });

        // Catégories avec icônes
        $categoryIcons = [
            'IA générative' => '🤖', 'Cybersécurité' => '🔒', 'Cloud' => '☁️',
            'Robotique' => '🦾', 'Données' => '📊', 'Startup' => '🚀',
            'Éducation tech' => '🎓', 'Infrastructure' => '🏗️', 'Autre' => '📰',
            'Général' => '📰',
        ];

        return view('news::public.index', compact('grouped', 'categoryIcons'));
    }

    public function show(NewsArticle $article): View
    {
        abort_if(! $article->is_published, 404);

        $article->load('source');

        return view('news::public.show', compact('article'));
    }
}
