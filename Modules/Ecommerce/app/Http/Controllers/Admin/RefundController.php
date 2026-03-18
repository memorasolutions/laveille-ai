<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Refund;
use Modules\Ecommerce\Services\RefundService;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService,
    ) {}

    public function index(): View
    {
        $refunds = Refund::with(['order', 'user'])
            ->latest()
            ->paginate(15);

        return view('ecommerce::admin.refunds.index', compact('refunds'));
    }

    public function approve(Request $request, Refund $refund): RedirectResponse
    {
        $this->refundService->approveRefund(
            $refund,
            $request->user(),
            $request->input('notes'),
        );

        return redirect()->route('admin.ecommerce.refunds.index')
            ->with('success', __('Remboursement approuvé.'));
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        $this->refundService->rejectRefund(
            $refund,
            $request->user(),
            $request->input('notes'),
        );

        return redirect()->route('admin.ecommerce.refunds.index')
            ->with('success', __('Remboursement refusé.'));
    }
}
