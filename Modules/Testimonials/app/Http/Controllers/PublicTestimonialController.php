<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Testimonials\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\SEO\Services\JsonLdService;
use Modules\Testimonials\Models\Testimonial;

class PublicTestimonialController extends Controller
{
    public function show(): View
    {
        $testimonials = Testimonial::approved()->ordered()->get();

        return view('testimonials::public.index', compact('testimonials'));
    }
}
