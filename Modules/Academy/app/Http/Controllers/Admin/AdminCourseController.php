<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Academy\Models\Course;

class AdminCourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::withCount('chapters')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('academy::admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        return view('academy::admin.courses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'subtitle'         => 'nullable|string|max:255',
            'summary'          => 'nullable|string|max:1000',
            'description'      => 'nullable|string',
            'language'         => 'required|string|max:10',
            'level'            => ['required', Rule::in(['intro', 'inter', 'avance'])],
            'duration_minutes' => 'nullable|integer|min:1',
            'image_media_id'   => 'nullable|integer',
            'visibility'       => ['required', Rule::in(['public', 'unlisted', 'private'])],
            'access_type'      => ['required', Rule::in(['free', 'paid_one_time', 'paid_subscription'])],
            'price_cents'      => 'nullable|integer|min:0',
            'currency'         => 'nullable|string|max:3',
            'stripe_price_id'  => 'nullable|string|max:255',
            'status'           => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ]);

        // Prix obligatoire pour les cours payants
        if (in_array($validated['access_type'], ['paid_one_time', 'paid_subscription'], true)) {
            if (empty($validated['price_cents']) || $validated['price_cents'] <= 0) {
                return back()
                    ->withInput()
                    ->withErrors(['price_cents' => 'Un prix supérieur à zéro est requis pour un cours payant.']);
            }
        } else {
            $validated['price_cents'] = null;
        }

        $validated['slug']       = Str::slug($validated['title']);
        $validated['status']     = $validated['status'] ?? 'draft';
        $validated['currency']   = $validated['currency'] ?? 'CAD';
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        Course::create($validated);

        return redirect()->route('admin.academy.courses.index')
            ->with('success', 'Cours créé avec succès.');
    }

    public function edit(Course $course): View
    {
        $course->load([
            'chapters.lessons',
            'courseRoles.user',
        ]);

        return view('academy::admin.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'subtitle'         => 'nullable|string|max:255',
            'summary'          => 'nullable|string|max:1000',
            'description'      => 'nullable|string',
            'language'         => 'required|string|max:10',
            'level'            => ['required', Rule::in(['intro', 'inter', 'avance'])],
            'duration_minutes' => 'nullable|integer|min:1',
            'image_media_id'   => 'nullable|integer',
            'visibility'       => ['required', Rule::in(['public', 'unlisted', 'private'])],
            'access_type'      => ['required', Rule::in(['free', 'paid_one_time', 'paid_subscription'])],
            'price_cents'      => 'nullable|integer|min:0',
            'currency'         => 'nullable|string|max:3',
            'stripe_price_id'  => 'nullable|string|max:255',
            'status'           => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if (in_array($validated['access_type'], ['paid_one_time', 'paid_subscription'], true)) {
            if (empty($validated['price_cents']) || $validated['price_cents'] <= 0) {
                return back()
                    ->withInput()
                    ->withErrors(['price_cents' => 'Un prix supérieur à zéro est requis pour un cours payant.']);
            }
        } else {
            $validated['price_cents'] = null;
        }

        $validated['currency']   = $validated['currency'] ?? 'CAD';
        $validated['updated_by'] = Auth::id();

        $course->update($validated);

        return redirect()->route('admin.academy.courses.index')
            ->with('success', 'Cours mis à jour avec succès.');
    }

    public function toggleStatus(Course $course): RedirectResponse
    {
        $newStatus = $course->status === 'published' ? 'draft' : 'published';

        $course->update([
            'status'       => $newStatus,
            'updated_by'   => Auth::id(),
            'published_at' => $newStatus === 'published' ? now() : $course->published_at,
        ]);

        $message = $newStatus === 'published' ? 'Cours publié.' : 'Cours dépublié (brouillon).';

        return redirect()->route('admin.academy.courses.index')
            ->with('success', $message);
    }

    public function archive(Course $course): RedirectResponse
    {
        $course->update([
            'status'     => 'archived',
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.academy.courses.index')
            ->with('success', 'Cours archivé.');
    }
}
