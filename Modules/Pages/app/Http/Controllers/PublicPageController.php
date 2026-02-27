<?php

declare(strict_types=1);

namespace Modules\Pages\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Pages\Models\StaticPage;

class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = StaticPage::where('slug->'.app()->getLocale(), $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $template = $page->template ?? 'default';
        $viewName = "pages::public.templates.{$template}";

        if (! view()->exists($viewName)) {
            $viewName = 'pages::public.templates.default';
        }

        return view($viewName, compact('page'));
    }
}
