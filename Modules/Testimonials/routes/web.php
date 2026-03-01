<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Testimonials\Http\Controllers\PublicTestimonialController;

Route::get('/testimonials', [PublicTestimonialController::class, 'show'])
    ->name('testimonials.show');
