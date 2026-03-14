<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Newsletter\Models\EmailWorkflow;
use Modules\Newsletter\Models\WorkflowEnrollment;
use Modules\Newsletter\Models\WorkflowStep;
use Modules\Newsletter\Models\WorkflowStepLog;
use Modules\Notifications\Models\EmailTemplate;

class WorkflowController extends Controller
{
    public function index()
    {
        $workflows = EmailWorkflow::withCount(['steps', 'enrollments'])
            ->latest()
            ->paginate(15);

        return view('newsletter::admin.workflows.index', compact('workflows'));
    }

    public function create()
    {
        $templates = EmailTemplate::where('module', 'newsletter')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('newsletter::admin.workflows.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:signup,purchase,custom_event,date_based,manual',
            'steps' => 'nullable|array',
            'steps.*.type' => 'required|in:send_email,delay,condition,action',
            'steps.*.template_id' => 'nullable|exists:email_templates,id',
            'steps.*.config' => 'nullable|array',
        ]);

        $workflow = EmailWorkflow::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'trigger_type' => $validated['trigger_type'],
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        if (! empty($validated['steps'])) {
            foreach ($validated['steps'] as $position => $stepData) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'type' => $stepData['type'],
                    'template_id' => $stepData['template_id'] ?? null,
                    'config' => $stepData['config'] ?? [],
                    'position' => $position,
                ]);
            }
        }

        return redirect()->route('admin.newsletter.workflows.index')
            ->with('success', 'Workflow créé avec succès.');
    }

    public function show(EmailWorkflow $workflow)
    {
        $workflow->load(['steps.template', 'creator']);

        $stats = [
            'active' => WorkflowEnrollment::where('workflow_id', $workflow->id)->where('status', 'active')->count(),
            'completed' => WorkflowEnrollment::where('workflow_id', $workflow->id)->where('status', 'completed')->count(),
            'cancelled' => WorkflowEnrollment::where('workflow_id', $workflow->id)->where('status', 'cancelled')->count(),
            'total_sent' => WorkflowStepLog::whereHas('enrollment', fn ($q) => $q->where('workflow_id', $workflow->id))
                ->where('status', 'sent')->count(),
            'total_failed' => WorkflowStepLog::whereHas('enrollment', fn ($q) => $q->where('workflow_id', $workflow->id))
                ->where('status', 'failed')->count(),
        ];

        return view('newsletter::admin.workflows.show', compact('workflow', 'stats'));
    }

    public function edit(EmailWorkflow $workflow)
    {
        $workflow->load('steps');
        $templates = EmailTemplate::where('module', 'newsletter')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('newsletter::admin.workflows.edit', compact('workflow', 'templates'));
    }

    public function update(Request $request, EmailWorkflow $workflow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:signup,purchase,custom_event,date_based,manual',
            'status' => 'nullable|in:draft,active,paused,archived',
            'steps' => 'nullable|array',
            'steps.*.type' => 'required|in:send_email,delay,condition,action',
            'steps.*.template_id' => 'nullable|exists:email_templates,id',
            'steps.*.config' => 'nullable|array',
        ]);

        $workflow->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'trigger_type' => $validated['trigger_type'],
            'status' => $validated['status'] ?? $workflow->status,
        ]);

        // Rebuild steps
        $workflow->steps()->delete();

        if (! empty($validated['steps'])) {
            foreach ($validated['steps'] as $position => $stepData) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'type' => $stepData['type'],
                    'template_id' => $stepData['template_id'] ?? null,
                    'config' => $stepData['config'] ?? [],
                    'position' => $position,
                ]);
            }
        }

        return redirect()->route('admin.newsletter.workflows.index')
            ->with('success', 'Workflow mis à jour.');
    }

    public function destroy(EmailWorkflow $workflow)
    {
        $workflow->delete();

        return redirect()->route('admin.newsletter.workflows.index')
            ->with('success', 'Workflow supprimé.');
    }

    public function activate(EmailWorkflow $workflow)
    {
        $workflow->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Workflow activé.');
    }

    public function pause(EmailWorkflow $workflow)
    {
        $workflow->update(['status' => 'paused']);

        return redirect()->back()->with('success', 'Workflow mis en pause.');
    }
}
