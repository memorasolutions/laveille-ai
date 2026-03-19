<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Faq\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Faq\Models\Faq;
use Nwidart\Modules\Facades\Module;

class PublicFaqController extends Controller
{
    public function index(): View
    {
        abort_unless(
            Module::has('FrontTheme') && Module::find('FrontTheme')?->isEnabled(),
            404
        );

        $faqs = Faq::published()->ordered()->get()->groupBy('category');

        return view('fronttheme::faq', compact('faqs'));
    }
}
