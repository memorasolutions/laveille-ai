<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tools\Models\SavedPrompt;

class SavedPromptController extends Controller
{
    public function index(): JsonResponse
    {
        $prompts = SavedPrompt::forUser(auth()->id())
            ->latest()
            ->paginate(20);

        return response()->json($prompts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'prompt_text' => 'required',
            'params' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $prompt = SavedPrompt::create([
            'user_id' => auth()->id(),
            ...$validated,
        ]);

        return response()->json($prompt, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $prompt = SavedPrompt::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|max:255',
            'prompt_text' => 'sometimes|required',
            'params' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $prompt->update($validated);

        return response()->json($prompt);
    }

    public function destroy(int $id): JsonResponse
    {
        $prompt = SavedPrompt::where('user_id', auth()->id())->findOrFail($id);
        $prompt->delete();

        return response()->json(null, 204);
    }
}
