<?php

declare(strict_types=1);

namespace Modules\FormBuilder\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\FormBuilder\Models\Form;
use Modules\FormBuilder\Models\FormSubmission;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function index(Form $form, Request $request): View
    {
        $submissions = $form->submissions()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('data', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(20);

        return view('formbuilder::admin.submissions.index', compact('form', 'submissions'));
    }

    public function show(Form $form, FormSubmission $submission): View
    {
        $submission->markAsRead();

        return view('formbuilder::admin.submissions.show', compact('form', 'submission'));
    }

    public function destroy(Form $form, FormSubmission $submission): RedirectResponse
    {
        $submission->delete();

        return redirect()->back()->with('success', 'Soumission supprimée.');
    }

    public function export(Form $form): StreamedResponse
    {
        $submissions = $form->submissions()->get();
        $fields = $form->fields()->orderBy('sort_order')->get();
        $fileName = 'form_' . $form->slug . '_' . now()->format('Y-m-d_H-i') . '.csv';

        $callback = function () use ($submissions, $fields): void {
            $file = fopen('php://output', 'w');
            assert($file !== false);

            $headers = ['ID', 'Date', 'Statut', 'IP'];
            foreach ($fields as $field) {
                $headers[] = $field->label;
            }
            fputcsv($file, $headers);

            foreach ($submissions as $submission) {
                $row = [
                    $submission->id,
                    $submission->created_at->format('Y-m-d H:i'),
                    $submission->status,
                    $submission->ip_address,
                ];
                foreach ($fields as $field) {
                    $row[] = $submission->data[$field->name] ?? '';
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
