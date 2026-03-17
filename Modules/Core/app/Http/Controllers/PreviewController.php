<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

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

        return view('core::preview', compact('model', 'type'));
    }
}
