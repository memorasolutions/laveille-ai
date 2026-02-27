<?php

use Illuminate\Support\Facades\Route;
use Modules\Testimonials\Http\Controllers\PublicTestimonialController;

Route::get('/testimonials', [PublicTestimonialController::class, 'show'])
    ->name('testimonials.show');
