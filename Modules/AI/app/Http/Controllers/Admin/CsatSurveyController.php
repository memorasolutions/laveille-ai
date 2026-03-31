<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\CsatSurvey;
use Modules\Settings\Facades\Settings;

class CsatSurveyController extends Controller
{
    public function index(): View
    {
        $surveys = CsatSurvey::with('ticket', 'user')->latest()->paginate((int) Settings::get('ai.csat_surveys_per_page', 30));
        $avgScore = CsatSurvey::averageScore();
        $totalSurveys = CsatSurvey::count();

        return view('ai::admin.csat.index', compact('surveys', 'avgScore', 'totalSurveys'));
    }

    public function destroy(CsatSurvey $survey): RedirectResponse
    {
        $survey->delete();

        return back()->with('success', __('L\'enquête a été supprimée.'));
    }

    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ticket_id' => 'nullable|exists:tickets,id',
            'conversation_id' => 'nullable|exists:ai_conversations,id',
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        CsatSurvey::create([
            'user_id' => auth()->id(),
            'ticket_id' => $data['ticket_id'] ?? null,
            'conversation_id' => $data['conversation_id'] ?? null,
            'score' => $data['score'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }
}
