<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Page admin de review des audits pricing : table diffs current vs audited,
 * accept/reject 1-clic, filtre fraîcheur, screenshot preview modale.
 */

namespace Modules\Directory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingAudit;

class PricingAuditController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'pending'); // pending|all|stale|fresh

        $query = ToolPricingAudit::query()
            ->with('tool')
            ->orderByDesc('audited_at');

        if ($filter === 'pending') {
            $query->where('review_status', 'pending');
        } elseif ($filter === 'stale') {
            $query->where('audited_at', '<', now()->subDays(90));
        } elseif ($filter === 'fresh') {
            $query->where('audited_at', '>=', now()->subDays(30));
        }

        $audits = $query->paginate(25);

        // Stats globales
        $stats = [
            'total' => ToolPricingAudit::count(),
            'pending' => ToolPricingAudit::where('review_status', 'pending')->count(),
            'accepted' => ToolPricingAudit::where('review_status', 'accepted')->count(),
            'rejected' => ToolPricingAudit::where('review_status', 'rejected')->count(),
            'stale' => ToolPricingAudit::where('audited_at', '<', now()->subDays(90))->count(),
            'tools_total' => Tool::published()->count(),
            'tools_audited' => ToolPricingAudit::distinct('directory_tool_id')->count('directory_tool_id'),
        ];

        return view('directory::admin.pricing-audit.index', compact('audits', 'filter', 'stats'));
    }

    public function accept(Request $request, ToolPricingAudit $audit): RedirectResponse
    {
        $audit->tool->update([
            'pricing' => $audit->real_pricing,
            'has_education_pricing' => $audit->has_education_discount ?? $audit->tool->has_education_pricing,
        ]);

        $audit->update([
            'review_status' => 'accepted',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('Audit accepté et appliqué.'));
    }

    public function reject(Request $request, ToolPricingAudit $audit): RedirectResponse
    {
        $audit->update([
            'review_status' => 'rejected',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('Audit rejeté.'));
    }
}
