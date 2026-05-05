<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tools\Models\SavedCrosswordPreset;

class SavedCrosswordPresetController
{
    public function index(): JsonResponse
    {
        return response()->json(SavedCrosswordPreset::forUser(auth()->id())->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'config_text' => 'required',
            'params' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        // 2026-05-05 #101+#113 : si is_public, vérifier doublon (chez quiconque, incluant le même user).
        if (! empty($validated['is_public'])) {
            $duplicate = $this->findPublicDuplicateByConfig($validated['config_text']);
            if ($duplicate) {
                $isOwn = $duplicate->user_id === auth()->id();
                $msg = $isOwn
                    ? 'Vous avez déjà publié une grille identique : « '.$duplicate->name.' ». Modifiez celle-ci au lieu d\'en créer un doublon, ou changez vos paires.'
                    : 'Une grille publique identique existe déjà : « '.$duplicate->name.' ». Vous pouvez la partager directement, ou modifier vos paires pour en créer une variante.';
                return response()->json([
                    'success' => false,
                    'duplicate' => true,
                    'duplicate_url' => url('/jeumc/'.($duplicate->custom_slug ?: $duplicate->public_id)),
                    'duplicate_name' => $duplicate->name,
                    'duplicate_is_own' => $isOwn,
                    'duplicate_edit_url' => $isOwn ? url('/outils/mots-croises?preset='.$duplicate->public_id) : null,
                    'duplicate_user_saved_url' => $isOwn ? url('/user/saved?type=crossword') : null,
                    'message' => $msg,
                ], 409);
            }
        }

        $preset = SavedCrosswordPreset::create([
            'user_id' => auth()->id(),
            ...$validated,
        ]);

        return response()->json($preset, 201);
    }

    public function update(Request $request, string $publicId): JsonResponse
    {
        $preset = SavedCrosswordPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|max:255',
            'config_text' => 'sometimes|required',
            'params' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        // 2026-05-05 #101+#113 : si bascule en public, vérifier doublon (chez quiconque, incluant le même user).
        $willBePublic = $validated['is_public'] ?? $preset->is_public;
        $configText = $validated['config_text'] ?? $preset->config_text;
        if ($willBePublic) {
            $duplicate = $this->findPublicDuplicateByConfig($configText, $preset->id);
            if ($duplicate) {
                $isOwn = $duplicate->user_id === auth()->id();
                $msg = $isOwn
                    ? 'Vous avez déjà publié une grille identique : « '.$duplicate->name.' ». Modifiez celle-ci au lieu d\'en créer un doublon, ou changez vos paires.'
                    : 'Une grille publique identique existe déjà : « '.$duplicate->name.' ». Vous pouvez la partager directement, ou modifier vos paires pour en créer une variante.';
                return response()->json([
                    'success' => false,
                    'duplicate' => true,
                    'duplicate_url' => url('/jeumc/'.($duplicate->custom_slug ?: $duplicate->public_id)),
                    'duplicate_name' => $duplicate->name,
                    'duplicate_is_own' => $isOwn,
                    'duplicate_edit_url' => $isOwn ? url('/outils/mots-croises?preset='.$duplicate->public_id) : null,
                    'duplicate_user_saved_url' => $isOwn ? url('/user/saved?type=crossword') : null,
                    'message' => $msg,
                ], 409);
            }
        }

        $preset->update($validated);

        return response()->json($preset);
    }

    /**
     * 2026-05-05 #101 : trouve un duplicate public par fingerprint, exclut $excludeId.
     */
    private function findPublicDuplicateByConfig(string $configText, ?int $excludeId = null): ?SavedCrosswordPreset
    {
        $fingerprint = SavedCrosswordPreset::computeFingerprint($configText);
        $query = SavedCrosswordPreset::where('fingerprint', $fingerprint)
            ->where('is_public', true);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function destroy(string $publicId): JsonResponse
    {
        $preset = SavedCrosswordPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();
        $preset->delete();

        return response()->json(null, 204);
    }
}
