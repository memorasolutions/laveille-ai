<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Review;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = Review::with(['product', 'user'])
            ->latest()
            ->paginate(20);

        return view('ecommerce::admin.reviews.index', compact('reviews'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => true]);

        return redirect()->back()->with('success', __('Avis approuvé avec succès.'));
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->back()->with('success', __('Avis rejeté et supprimé.'));
    }
}
