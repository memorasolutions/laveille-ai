<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\IntakeQuestion;

class IntakeQuestionController extends Controller
{
    public function index(BookingService $service)
    {
        $questions = IntakeQuestion::forService($service->id)->ordered()->get();

        return view('booking::admin.intake-questions.index', compact('service', 'questions'));
    }

    public function create(BookingService $service)
    {
        return view('booking::admin.intake-questions.form', compact('service'));
    }

    public function store(Request $request, BookingService $service)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:text,textarea,select,checkbox,radio,number,email,phone',
            'options' => 'nullable|string',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if (! empty($validated['options'])) {
            $validated['options'] = array_filter(array_map('trim', explode("\n", $validated['options'])));
        }

        $validated['service_id'] = $service->id;
        IntakeQuestion::create($validated);

        return redirect()->route('admin.booking.intake-questions.index', $service)
            ->with('success', __('Question ajoutée.'));
    }

    public function edit(IntakeQuestion $intakeQuestion)
    {
        $service = $intakeQuestion->service;

        return view('booking::admin.intake-questions.form', compact('intakeQuestion', 'service'));
    }

    public function update(Request $request, IntakeQuestion $intakeQuestion)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:text,textarea,select,checkbox,radio,number,email,phone',
            'options' => 'nullable|string',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if (! empty($validated['options'])) {
            $validated['options'] = array_filter(array_map('trim', explode("\n", $validated['options'])));
        }

        $intakeQuestion->update($validated);

        return redirect()->route('admin.booking.intake-questions.index', $intakeQuestion->service)
            ->with('success', __('Question mise à jour.'));
    }

    public function destroy(IntakeQuestion $intakeQuestion)
    {
        $service = $intakeQuestion->service;
        $intakeQuestion->delete();

        return redirect()->route('admin.booking.intake-questions.index', $service)
            ->with('success', __('Question supprimée.'));
    }
}
