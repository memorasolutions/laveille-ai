<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Nwidart\Modules\Facades\Module;

class ResourceHubController extends Controller
{
    public function index(): View
    {
        $sections = [];

        if (Module::has('Directory') && class_exists(\Modules\Directory\Models\Tool::class) && Route::has('directory.index')) {
            $model = \Modules\Directory\Models\Tool::class;
            $sections[] = [
                'icon' => '🤖',
                'title' => __('Répertoire techno'),
                'description' => __('Répertoire d\'outils d\'intelligence artificielle et de technologies innovantes.'),
                'count' => $model::where('status', 'published')->count(),
                'url' => route('directory.index'),
                'updated_at' => $model::latest()->value('updated_at'),
            ];
        }

        if (Module::has('Dictionary') && class_exists(\Modules\Dictionary\Models\Term::class) && Route::has('dictionary.index')) {
            $model = \Modules\Dictionary\Models\Term::class;
            $sections[] = [
                'icon' => '📖',
                'title' => __('Glossaire IA'),
                'description' => __('Glossaire terminologique sur l\'intelligence artificielle et les technologies.'),
                'count' => $model::count(),
                'url' => route('dictionary.index'),
                'updated_at' => $model::latest()->value('updated_at'),
            ];
        }

        if (Module::has('Acronyms') && class_exists(\Modules\Acronyms\Models\Acronym::class) && Route::has('acronyms.index')) {
            $model = \Modules\Acronyms\Models\Acronym::class;
            $sections[] = [
                'icon' => '🔤',
                'title' => __('Acronymes éducation'),
                'description' => __('Liste d\'acronymes du milieu de l\'éducation au Québec.'),
                'count' => $model::count(),
                'url' => route('acronyms.index'),
                'updated_at' => $model::latest()->value('updated_at'),
            ];
        }

        if (Module::has('Blog') && class_exists(\Modules\Blog\Models\Article::class) && Route::has('blog.index')) {
            $model = \Modules\Blog\Models\Article::class;
            $sections[] = [
                'icon' => '✏️',
                'title' => __('Blog'),
                'description' => __('Articles de veille, analyses et réflexions sur l\'IA et les technologies.'),
                'count' => $model::published()->count(),
                'url' => route('blog.index'),
                'updated_at' => $model::latest('published_at')->value('published_at'),
            ];
        }

        if (Module::has('News') && class_exists(\Modules\News\Models\NewsArticle::class) && Route::has('news.index')) {
            $model = \Modules\News\Models\NewsArticle::class;
            $sections[] = [
                'icon' => '📰',
                'title' => __('Actualités'),
                'description' => __('Agrégation de nouvelles spécialisées en technologie et intelligence artificielle.'),
                'count' => $model::count(),
                'url' => route('news.index'),
                'updated_at' => $model::latest()->value('updated_at'),
            ];
        }

        if (Route::has('tools.index')) {
            $sections[] = [
                'icon' => '🛠️',
                'title' => __('Outils gratuits'),
                'description' => __('Calculatrice, générateur de mot de passe, compteur de mots et autres utilitaires.'),
                'count' => 9,
                'url' => route('tools.index'),
                'updated_at' => null,
            ];
        }

        if (Route::has('prompts.index') && class_exists(\Modules\Tools\Models\SavedPrompt::class)) {
            $sections[] = [
                'icon' => '✨',
                'title' => __('Bibliothèque de prompts'),
                'description' => __('Prompts IA partagés par la communauté, prêts à copier et utiliser.'),
                'count' => \Modules\Tools\Models\SavedPrompt::public()->count(),
                'url' => route('prompts.index'),
                'updated_at' => \Modules\Tools\Models\SavedPrompt::public()->latest()->value('created_at'),
            ];
        }

        if (Route::has('shorturl.create')) {
            $sections[] = [
                'icon' => '🔗',
                'title' => __('Raccourcisseur d\'URL'),
                'description' => __('Raccourcissez vos liens avec veille.la, go3.ca et d\'autres domaines.'),
                'count' => null,
                'url' => route('shorturl.create'),
                'updated_at' => null,
            ];
        }

        return view('fronttheme::ressources', compact('sections'));
    }
}
