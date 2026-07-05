<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Petites préférences par outil, par utilisateur connecté (ex. couleurs personnalisées
 * récentes). Générique et réutilisable : chaque outil lit/écrit sa propre clé sans migration
 * dédiée, dans users.tool_preferences (JSON).
 */
class ToolPreferenceController
{
    public function show(string $tool): JsonResponse
    {
        $prefs = auth()->user()->tool_preferences[$tool] ?? [];

        return response()->json(['preferences' => $prefs]);
    }

    public function update(Request $request, string $tool): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'regex:/^[a-z_]{1,40}$/'],
            'value' => ['required'],
        ]);

        $key = $validated['key'];
        $value = $validated['value'];

        if ($key === 'custom_colors') {
            $value = $this->sanitizeCustomColors($value);
        } elseif ($key === 'custom_durations') {
            $value = $this->sanitizeCustomDurations($value);
        } elseif (strlen(json_encode($value) ?: '') > 2000) {
            throw ValidationException::withMessages(['value' => 'Trop volumineux.']);
        }

        $user = auth()->user();
        $prefs = $user->tool_preferences ?? [];
        $prefs[$tool] = array_merge($prefs[$tool] ?? [], [$key => $value]);
        $user->update(['tool_preferences' => $prefs]);

        return response()->json(['preferences' => $prefs[$tool]]);
    }

    private function sanitizeCustomColors(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['value' => 'Format de couleurs invalide.']);
        }

        $colors = array_values(array_filter($value, fn ($c) => is_string($c) && preg_match('/^#[0-9a-f]{6}$/i', $c)));

        return array_slice($colors, 0, 5);
    }

    private function sanitizeCustomDurations(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['value' => 'Format de durées invalide.']);
        }

        $minutes = array_values(array_unique(array_filter($value, fn ($m) => is_int($m) && $m >= 1 && $m <= 180)));

        return array_slice($minutes, 0, 2);
    }
}
