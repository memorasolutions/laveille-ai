<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Testimonials\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Testimonials\Models\Testimonial;

class TestimonialController extends Controller
{
    public function index(): View
    {
        $testimonials = Testimonial::ordered()->get();

        return view('testimonials::admin.index', compact('testimonials'));
    }

    public function create(): View
    {
        return view('testimonials::admin.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'is_approved' => 'boolean',
        ]);

        $validated['content'] = clean($validated['content']);
        $validated['order'] = (int) Testimonial::max('order') + 1;

        Testimonial::create($validated);

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Témoignage créé avec succès.');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('testimonials::admin.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'is_approved' => 'boolean',
        ]);

        $validated['content'] = clean($validated['content']);
        $testimonial->update($validated);

        return redirect()->back()
            ->with('success', 'Témoignage mis à jour.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Témoignage supprimé.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*' => 'integer|exists:testimonials,id',
        ]);

        foreach ($validated['items'] as $order => $id) {
            Testimonial::where('id', $id)->update(['order' => $order]);
        }

        return response()->json(['success' => true, 'message' => 'Ordre mis à jour.']);
    }
}
