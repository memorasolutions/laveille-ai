<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tools\Models\SavedDrawPreset;

class SavedDrawPresetController
{
    public function index(): JsonResponse
    {
        return response()->json(SavedDrawPreset::forUser(auth()->id())->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'config_text' => 'required',
            'params' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $preset = SavedDrawPreset::create([
            'user_id' => auth()->id(),
            ...$validated,
        ]);

        return response()->json($preset, 201);
    }

    public function update(Request $request, string $publicId): JsonResponse
    {
        $preset = SavedDrawPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|max:255',
            'config_text' => 'sometimes|required',
            'params' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $preset->update($validated);

        return response()->json($preset);
    }

    public function destroy(string $publicId): JsonResponse
    {
        $preset = SavedDrawPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();
        $preset->delete();

        return response()->json(null, 204);
    }
}
