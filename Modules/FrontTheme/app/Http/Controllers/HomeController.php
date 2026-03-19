<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Nwidart\Modules\Facades\Module;

class HomeController extends Controller
{
    public function index(): View
    {
        $articles = collect();

        $articleClass = 'Modules\\Blog\\Models\\Article';

        if (Module::has('Blog') && Module::find('Blog')?->isEnabled() && class_exists($articleClass)) {
            $articles = $articleClass::query()
                ->published()
                ->with(['user', 'blogCategory'])
                ->latest('published_at')
                ->take(12)
                ->get();
        }

        return view('fronttheme::home', compact('articles'));
    }
}
