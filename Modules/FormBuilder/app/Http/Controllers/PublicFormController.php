<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FormBuilder\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\FormBuilder\Models\Form;
use Modules\FormBuilder\Models\FormSubmission;

class PublicFormController extends Controller
{
    public function show(Form $form): View
    {
        abort_if(! $form->is_published, 404);

        return view('formbuilder::public.show', compact('form'));
    }

    public function submit(Request $request, Form $form): RedirectResponse
    {
        abort_if(! $form->is_published, 404);

        // Honeypot - si rempli, bot détecté, redirect silencieux
        if ($request->filled('_honeypot')) {
            return redirect()->back();
        }

        // Rate limiting : 5 soumissions/minute par IP
        $key = 'form_submit_'.$form->id.'_'.$request->ip();
        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= 5) {
            abort(429, 'Trop de soumissions. Réessayez dans une minute.');
        }

        Cache::put($key, $attempts + 1, now()->addMinute());

        // Validation dynamique basée sur les champs du formulaire
        $rules = [];
        $form->load('fields');

        foreach ($form->fields as $field) {
            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($field->type === 'email') {
                $fieldRules[] = 'email';
            }
            if ($field->type === 'number') {
                $fieldRules[] = 'numeric';
            }
            if (! empty($field->validation_rules)) {
                $fieldRules[] = $field->validation_rules;
            }

            $rules['fields.'.$field->name] = implode('|', $fieldRules);
        }

        $validated = $request->validate($rules);

        FormSubmission::create([
            'form_id' => $form->id,
            'data' => $validated['fields'] ?? [],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Formulaire envoyé avec succès !');
    }
}
