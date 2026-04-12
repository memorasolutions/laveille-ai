<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

class PreviewController
{
    public function __invoke(string $token): View
    {
        $model = null;
        $type = null;

        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $model = \Modules\Blog\Models\Article::where('preview_token', $token)->first();
            if ($model) {
                $type = 'article';
            }
        }

        if (! $model && class_exists(\Modules\Pages\Models\StaticPage::class)) {
            $model = \Modules\Pages\Models\StaticPage::where('preview_token', $token)->first();
            if ($model) {
                $type = 'page';
            }
        }

        if (! $model) {
            abort(404);
        }

        if ($type === 'article' && ViewFacade::exists('fronttheme::blog.show')) {
            // Pas de eager load — l'Article n'a pas toujours les relations chargées selon le module

            return view('fronttheme::blog.show', [
                'article' => $model,
                'articleContent' => $model->content,
                'schemaJson' => '{}',
                'blogPostingJsonLd' => '',
                'similarArticles' => collect(),
                'relatedArticles' => collect(),
                'isPreview' => true,
            ]);
        }

        return view('core::preview', compact('model', 'type'));
    }
}
