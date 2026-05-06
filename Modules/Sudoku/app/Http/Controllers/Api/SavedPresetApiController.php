<?php

declare(strict_types=1);

namespace Modules\Sudoku\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sudoku\Models\SavedSudokuPreset;

class SavedPresetApiController extends Controller
{
    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'puzzle_id' => 'required|integer|exists:sudoku_puzzles,id',
            'pseudo' => 'nullable|string|max:30',
            'grid_state' => 'required|array|size:9',
            'grid_state.*' => 'array|size:9',
            'grid_state.*.*' => 'nullable|integer|between:0,9',
            'time_elapsed' => 'nullable|integer|min:0',
            'hints_used' => 'nullable|integer|min:0',
            'errors_count' => 'nullable|integer|min:0',
        ]);

        $userId = $request->user()?->id;
        $pseudo = $data['pseudo'] ?? null;

        if (! $userId && ! $pseudo) {
            return response()->json(['success' => false, 'error' => 'Auth ou pseudo requis'], 400);
        }

        $attrs = [
            'grid_state' => $data['grid_state'],
            'time_elapsed' => $data['time_elapsed'] ?? 0,
            'hints_used' => $data['hints_used'] ?? 0,
            'errors_count' => $data['errors_count'] ?? 0,
            'last_saved_at' => now(),
        ];

        $key = $userId
            ? ['user_id' => $userId, 'puzzle_id' => $data['puzzle_id']]
            : ['pseudo' => $pseudo, 'puzzle_id' => $data['puzzle_id']];

        $preset = SavedSudokuPreset::updateOrCreate($key, array_merge($key, $attrs));

        return response()->json(['success' => true, 'preset_id' => $preset->id]);
    }

    public function restore(Request $request, int $puzzle_id): JsonResponse
    {
        $userId = $request->user()?->id;
        $pseudo = $request->query('pseudo');

        if (! $userId && ! $pseudo) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $query = SavedSudokuPreset::where('puzzle_id', $puzzle_id);
        $userId ? $query->forUser($userId) : $query->forPseudo($pseudo);

        $preset = $query->first();

        if (! $preset || $preset->last_saved_at->lt(now()->subDays(7))) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($preset);
    }

    public function destroy(Request $request, int $puzzle_id): JsonResponse
    {
        $userId = $request->user()?->id;
        $pseudo = $request->query('pseudo');

        if (! $userId && ! $pseudo) {
            return response()->json(['success' => false], 400);
        }

        $query = SavedSudokuPreset::where('puzzle_id', $puzzle_id);
        $userId ? $query->forUser($userId) : $query->forPseudo($pseudo);

        $deleted = $query->delete();

        return response()->json(['success' => (bool) $deleted]);
    }
}
