<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Directory\Models\ToolSuggestion;

class RoadmapController extends Controller
{
    public function index(): View
    {
        $suggestions = ToolSuggestion::whereIn('status', ['pending', 'approved', 'planned', 'in_progress', 'done'])
            ->with('user', 'tool')
            ->orderByDesc('votes_count')
            ->get()
            ->groupBy('status');

        return view('directory::public.roadmap', compact('suggestions'));
    }

    public function vote(Request $request, int $id): JsonResponse
    {
        $suggestion = ToolSuggestion::findOrFail($id);
        $userId = Auth::id();

        $exists = DB::table('suggestion_votes')
            ->where('user_id', $userId)
            ->where('suggestion_id', $id)
            ->exists();

        if ($exists) {
            DB::table('suggestion_votes')->where('user_id', $userId)->where('suggestion_id', $id)->delete();
            $suggestion->decrement('votes_count');
        } else {
            DB::table('suggestion_votes')->insert([
                'user_id' => $userId,
                'suggestion_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $suggestion->increment('votes_count');
        }

        return response()->json(['votes' => $suggestion->fresh()->votes_count, 'voted' => ! $exists]);
    }
}
