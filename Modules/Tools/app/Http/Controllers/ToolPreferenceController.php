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
        } elseif ($key === 'favorite_colors') {
            $value = $this->sanitizeCustomColors($value, 2);
        } elseif ($key === 'custom_durations') {
            $value = $this->sanitizeCustomDurations($value);
        } elseif ($key === 'traffic_thresholds') {
            $value = $this->sanitizeTrafficThresholds($value);
        } elseif ($key === 'default_color') {
            $value = $this->sanitizeSingleColor($value);
        } elseif (strlen(json_encode($value) ?: '') > 2000) {
            throw ValidationException::withMessages(['value' => 'Trop volumineux.']);
        }

        $user = auth()->user();
        $prefs = $user->tool_preferences ?? [];
        $prefs[$tool] = array_merge($prefs[$tool] ?? [], [$key => $value]);
        $user->update(['tool_preferences' => $prefs]);

        return response()->json(['preferences' => $prefs[$tool]]);
    }

    private function sanitizeCustomColors(mixed $value, int $max = 5): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['value' => 'Format de couleurs invalide.']);
        }

        $colors = array_values(array_filter($value, fn ($c) => is_string($c) && preg_match('/^#[0-9a-f]{6}$/i', $c)));

        return array_slice($colors, 0, $max);
    }

    // #843-846 : stocke désormais des TOTAUX EN SECONDES (pas des minutes) - le champ
    // "Durée personnalisée" accepte aussi des secondes (0-59) en plus des minutes.
    // Borne haute = 180 min 59 s (10859s), reprend le plafond de 180 min existant.
    private function sanitizeCustomDurations(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['value' => 'Format de durées invalide.']);
        }

        $seconds = array_values(array_unique(array_filter($value, fn ($s) => is_int($s) && $s >= 1 && $s <= 10859)));

        return array_slice($seconds, 0, 2);
    }

    private function sanitizeTrafficThresholds(mixed $value): array
    {
        if (! is_array($value) || ! isset($value['green'], $value['yellow'])) {
            throw ValidationException::withMessages(['value' => 'Format de seuils invalide.']);
        }

        $green = (int) $value['green'];
        $yellow = (int) $value['yellow'];

        if ($green < 2 || $green > 99 || $yellow < 1 || $yellow >= $green) {
            throw ValidationException::withMessages(['value' => 'Seuils invalides (jaune doit être inférieur à vert).']);
        }

        return ['green' => $green, 'yellow' => $yellow];
    }

    private function sanitizeSingleColor(mixed $value): string
    {
        if (! is_string($value) || ! preg_match('/^#[0-9a-f]{6}$/i', $value)) {
            throw ValidationException::withMessages(['value' => 'Couleur invalide.']);
        }

        return $value;
    }
}
