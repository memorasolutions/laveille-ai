<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FormBuilder\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\FormBuilder\Models\Form;
use Modules\FormBuilder\Models\FormField;

class FormController extends Controller
{
    public function index(): View
    {
        $forms = Form::withCount('submissions')->latest()->paginate(15);

        return view('formbuilder::admin.forms.index', compact('forms'));
    }

    public function create(): View
    {
        return view('formbuilder::admin.forms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        Form::create($validated);

        return redirect()->route('admin.formbuilder.forms.index')
            ->with('success', 'Formulaire créé avec succès.');
    }

    public function edit(Form $form): View
    {
        $form->load(['fields' => fn ($q) => $q->orderBy('sort_order')]);

        return view('formbuilder::admin.forms.edit', compact('form'));
    }

    public function update(Request $request, Form $form): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:forms,slug,' . $form->id,
            'description' => 'nullable|string',
            'is_published' => 'boolean',
            'fields' => 'nullable|array',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:' . implode(',', FormField::TYPES),
            'fields.*.is_required' => 'nullable|boolean',
            'fields.*.sort_order' => 'nullable|integer|min:0',
            'fields.*.options' => 'nullable|string',
        ]);

        $form->update($validated);

        // Sync fields
        $existingIds = [];
        foreach ($validated['fields'] ?? [] as $fieldData) {
            $options = ! empty($fieldData['options'])
                ? array_map('trim', explode(',', $fieldData['options']))
                : null;

            $field = $form->fields()->updateOrCreate(
                ['id' => $fieldData['id'] ?? null],
                [
                    'label' => $fieldData['label'],
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'is_required' => (bool) ($fieldData['is_required'] ?? false),
                    'sort_order' => (int) ($fieldData['sort_order'] ?? 0),
                    'options' => $options,
                ]
            );
            $existingIds[] = $field->id;
        }

        // Delete removed fields
        $form->fields()->whereNotIn('id', $existingIds)->delete();

        return redirect()->back()->with('success', 'Formulaire mis à jour.');
    }

    public function destroy(Form $form): RedirectResponse
    {
        $form->delete();

        return redirect()->back()->with('success', 'Formulaire supprimé.');
    }
}
