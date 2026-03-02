<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ABTest\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\ABTest\Models\Experiment;

class ExperimentController extends Controller
{
    public function index(): View
    {
        $experiments = Experiment::latest()->paginate(15);

        return view('abtest::admin.experiments.index', compact('experiments'));
    }

    public function create(): View
    {
        return view('abtest::admin.experiments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'variants' => 'required|string',
        ]);

        $variants = array_map('trim', explode(',', $validated['variants']));

        Experiment::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'variants' => $variants,
        ]);

        return redirect()->route('admin.experiments.index')
            ->with('success', 'Experience creee avec succes.');
    }

    public function show(Experiment $experiment): View
    {
        $results = $experiment->getResults();

        return view('abtest::admin.experiments.show', compact('experiment', 'results'));
    }

    public function start(Experiment $experiment): RedirectResponse
    {
        $experiment->start();

        return back()->with('success', 'Experience demarree.');
    }

    public function complete(Request $request, Experiment $experiment): RedirectResponse
    {
        $validated = $request->validate([
            'winner' => 'required|string',
        ]);

        $experiment->complete($validated['winner']);

        return back()->with('success', 'Experience terminee.');
    }

    public function destroy(Experiment $experiment): RedirectResponse
    {
        $experiment->delete();

        return redirect()->route('admin.experiments.index')
            ->with('success', 'Experience supprimee.');
    }
}
