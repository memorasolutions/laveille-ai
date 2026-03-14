<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\CannedReply;

class CannedReplyController extends Controller
{
    public function index(): View
    {
        $replies = CannedReply::with('user')->orderBy('sort_order')->get();

        return view('ai::admin.canned-replies.index', compact('replies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'shortcut' => 'nullable|string|max:50|unique:canned_replies,shortcut',
            'category' => 'nullable|string|max:100',
        ]);

        CannedReply::create([
            ...$validated,
            'user_id' => $request->boolean('shared') ? null : auth()->id(),
            'is_active' => true,
        ]);

        return redirect()->route('admin.ai.canned-replies.index')
            ->with('success', __('Réponse prédéfinie créée.'));
    }

    public function update(Request $request, CannedReply $cannedReply): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'shortcut' => 'nullable|string|max:50|unique:canned_replies,shortcut,'.$cannedReply->id,
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $cannedReply->update($validated);

        return redirect()->route('admin.ai.canned-replies.index')
            ->with('success', __('Réponse prédéfinie mise à jour.'));
    }

    public function destroy(CannedReply $cannedReply): RedirectResponse
    {
        $cannedReply->delete();

        return redirect()->route('admin.ai.canned-replies.index')
            ->with('success', __('Réponse prédéfinie supprimée.'));
    }
}
