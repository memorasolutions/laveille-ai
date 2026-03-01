<?php

declare(strict_types=1);

namespace Modules\CustomFields\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\CustomFields\Models\CustomFieldDefinition;

class CustomFieldDefinitionController extends Controller
{
    public function index(): View
    {
        $definitions = CustomFieldDefinition::orderBy('model_type')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('model_type');

        return view('customfields::admin.index', compact('definitions'));
    }

    public function create(): View
    {
        return view('customfields::admin.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'nullable|string|max:100|unique:custom_field_definitions,key',
            'type' => 'required|in:' . implode(',', CustomFieldDefinition::TYPES),
            'model_type' => 'required|in:' . implode(',', array_keys(CustomFieldDefinition::MODEL_TYPES)),
            'options' => 'nullable|string',
            'validation_rules' => 'nullable|string|max:255',
            'default_value' => 'nullable|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (! empty($validated['options'])) {
            $validated['options'] = array_map('trim', explode(',', $validated['options']));
        } else {
            $validated['options'] = null;
        }

        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = CustomFieldDefinition::forModel($validated['model_type'])->count();

        CustomFieldDefinition::create($validated);

        return redirect()->route('admin.custom-fields.index')
            ->with('success', 'Champ personnalisé créé avec succès.');
    }

    public function edit(CustomFieldDefinition $custom_field): View
    {
        return view('customfields::admin.edit', ['definition' => $custom_field]);
    }

    public function update(Request $request, CustomFieldDefinition $custom_field): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'nullable|string|max:100|unique:custom_field_definitions,key,' . $custom_field->id,
            'type' => 'required|in:' . implode(',', CustomFieldDefinition::TYPES),
            'model_type' => 'required|in:' . implode(',', array_keys(CustomFieldDefinition::MODEL_TYPES)),
            'options' => 'nullable|string',
            'validation_rules' => 'nullable|string|max:255',
            'default_value' => 'nullable|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (! empty($validated['options'])) {
            $validated['options'] = array_map('trim', explode(',', $validated['options']));
        } else {
            $validated['options'] = null;
        }

        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_active'] = $request->boolean('is_active');

        $custom_field->update($validated);

        return redirect()->route('admin.custom-fields.index')
            ->with('success', 'Champ personnalisé mis à jour.');
    }

    public function destroy(CustomFieldDefinition $custom_field): RedirectResponse
    {
        $custom_field->delete();

        return redirect()->route('admin.custom-fields.index')
            ->with('success', 'Champ personnalisé supprimé.');
    }
}
