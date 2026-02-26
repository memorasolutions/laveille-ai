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

        return view('pages::public.show', compact('page'));
    }
}
