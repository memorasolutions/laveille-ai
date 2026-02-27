<?php

declare(strict_types=1);

namespace Modules\Faq\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Faq\Models\Faq;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::ordered()->get();

        return view('faq::admin.index', compact('faqs'));
    }

    public function create(): View
    {
        return view('faq::admin.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_published' => 'boolean',
        ]);

        $validated['answer'] = clean($validated['answer']);
        $validated['order'] = (int) Faq::max('order') + 1;

        Faq::create($validated);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Question FAQ créée avec succès.');
    }

    public function edit(Faq $faq): View
    {
        return view('faq::admin.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_published' => 'boolean',
        ]);

        $validated['answer'] = clean($validated['answer']);

        $faq->update($validated);

        return redirect()->back()
            ->with('success', 'Question FAQ mise à jour.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Question FAQ supprimée.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*' => 'integer|exists:faqs,id',
        ]);

        foreach ($validated['items'] as $order => $id) {
            Faq::where('id', $id)->update(['order' => $order]);
        }

        return response()->json(['success' => true, 'message' => 'Ordre mis à jour.']);
    }
}
