<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Editor\Models\Shortcode;

class ShortcodeController
{
    public function index(): View
    {
        return view('backoffice::shortcodes.index');
    }

    public function create(): View
    {
        return view('backoffice::shortcodes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tag' => 'required|string|max:50|unique:shortcodes|regex:/^[a-z][a-z0-9_]*$/',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'html_template' => 'required|string',
            'parameters' => 'nullable|string',
            'has_content' => 'boolean',
        ]);

        $data = $validated;
        if ($request->has('parameters') && $request->parameters !== null) {
            $data['parameters'] = json_decode($request->parameters, true);
        }

        Shortcode::create($data);

        return redirect()->route('admin.shortcodes.index')->with('success', 'Shortcode créé avec succès.');
    }

    public function edit(Shortcode $shortcode): View
    {
        return view('backoffice::shortcodes.edit', compact('shortcode'));
    }

    public function update(Request $request, Shortcode $shortcode): RedirectResponse
    {
        $validated = $request->validate([
            'tag' => 'required|string|max:50|regex:/^[a-z][a-z0-9_]*$/|unique:shortcodes,tag,'.$shortcode->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'html_template' => 'required|string',
            'parameters' => 'nullable|string',
            'has_content' => 'boolean',
        ]);

        $data = $validated;
        if ($request->has('parameters') && $request->parameters !== null) {
            $data['parameters'] = json_decode($request->parameters, true);
        }

        $shortcode->update($data);

        return redirect()->route('admin.shortcodes.index')->with('success', 'Shortcode mis à jour avec succès.');
    }

    public function destroy(Shortcode $shortcode): RedirectResponse
    {
        $shortcode->delete();

        return redirect()->route('admin.shortcodes.index')->with('success', 'Shortcode supprimé avec succès.');
    }
}
