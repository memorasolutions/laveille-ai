<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Pages\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Modules\Pages\Models\StaticPage;
use Nwidart\Modules\Facades\Module;

class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        abort_unless(
            Module::has('FrontTheme') && Module::find('FrontTheme')?->isEnabled(),
            404
        );

        $locale = app()->getLocale();

        $page = StaticPage::query()
            ->where('status', 'published')
            ->where(function (Builder $q) use ($slug, $locale) {
                $q->where("slug->{$locale}", $slug)
                    ->orWhere('slug', $slug);
            })
            ->firstOrFail();

        return view('fronttheme::page', compact('page'));
    }
}
