<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            // 'present' (pas 'required') : un tableau vide est une valeur légitime (ex. supprimer
            // la dernière carte personnalisée envoie value=[] - 'required' le rejetait à tort (422),
            // empêchant la suppression de persister côté serveur malgré le retrait visuel côté client.
            'value' => ['present'],
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
        } elseif ($key === 'prompt_profile') {
            $value = $this->sanitizePromptProfile($value);
        } elseif ($key === 'custom_cards') {
            $value = $this->sanitizeCustomCards($value);
        } elseif ($key === 'tip_base') {
            $value = $this->sanitizeTipBase($value);
        } elseif (strlen(json_encode($value) ?: '') > 2000) {
            throw ValidationException::withMessages(['value' => __('Trop volumineux.')]);
        }

        // Round 40 (2026-07-27) : lecture-modification-écriture non atomique sur la colonne JSON
        // partagée tool_preferences - 2 requêtes concurrentes sur des CLÉS différentes (ex. l'onglet
        // wizard qui persiste custom_cards pendant que /user/prompts persiste prompt_profile) pouvaient
        // silencieusement s'écraser l'une l'autre (la file d'attente client des rounds 37-38 ne protège
        // que les mutations d'une même page). Verrou de ligne + transaction : atomique côté serveur,
        // couvre TOUTES les clés/pages/onglets pour un même utilisateur.
        $authUser = auth()->user();
        $toolPrefs = DB::transaction(function () use ($authUser, $tool, $key, $value) {
            $locked = \App\Models\User::query()->whereKey($authUser->getKey())->lockForUpdate()->firstOrFail();
            $prefs = $locked->tool_preferences ?? [];
            $prefs[$tool] = array_merge($prefs[$tool] ?? [], [$key => $value]);
            $locked->update(['tool_preferences' => $prefs]);

            // Garde l'instance auth() courante synchronisée avec l'état fraîchement écrit
            // (sans requête DB supplémentaire) - évite qu'un appel ultérieur dans la même
            // requête/le même test lise une valeur périmée sur l'objet $authUser d'origine.
            $authUser->tool_preferences = $prefs;
            $authUser->syncOriginal();

            return $prefs[$tool];
        });

        return response()->json(['preferences' => $toolPrefs]);
    }

    private function sanitizeCustomColors(mixed $value, int $max = 5): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['value' => __('Format de couleurs invalide.')]);
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
            throw ValidationException::withMessages(['value' => __('Format de durées invalide.')]);
        }

        $seconds = array_values(array_unique(array_filter($value, fn ($s) => is_int($s) && $s >= 1 && $s <= 10859)));

        return array_slice($seconds, 0, 2);
    }

    private function sanitizeTrafficThresholds(mixed $value): array
    {
        if (! is_array($value) || ! isset($value['green'], $value['yellow'])) {
            throw ValidationException::withMessages(['value' => __('Format de seuils invalide.')]);
        }

        $green = (int) $value['green'];
        $yellow = (int) $value['yellow'];

        if ($green < 2 || $green > 99 || $yellow < 1 || $yellow >= $green) {
            throw ValidationException::withMessages(['value' => __('Seuils invalides (jaune doit être inférieur à vert).')]);
        }

        return ['green' => $green, 'yellow' => $yellow];
    }

    /**
     * Base du pourboire de la calculatrice de taxes (#P0-audit 2026-08-30) : "before" (montant
     * avant taxes, usage courant au Québec) ou "after" (montant avec taxes). Whitelist stricte
     * plutôt que le repli générique < 2000 octets - seules deux valeurs ont un sens.
     */
    private function sanitizeTipBase(mixed $value): string
    {
        return in_array($value, ['before', 'after'], true) ? $value : 'before';
    }

    private function sanitizeSingleColor(mixed $value): string
    {
        if (! is_string($value) || ! preg_match('/^#[0-9a-f]{6}$/i', $value)) {
            throw ValidationException::withMessages(['value' => __('Couleur invalide.')]);
        }

        return $value;
    }

    /**
     * Profil utilisateur léger réutilisé par le constructeur de prompts (pré-remplissage
     * automatique) : 3 champs texte libre, whitelist stricte, tronqués plutôt que rejetés
     * (UX plus tolérante pour du texte libre de profil).
     */
    private function sanitizePromptProfile(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['value' => __('Format de profil invalide.')]);
        }

        $maxLengths = [
            'profile_role' => 80,
            'profile_style' => 80,
            'profile_constraints' => 500,
        ];

        $result = [];

        foreach ($maxLengths as $key => $maxLength) {
            if (! array_key_exists($key, $value)) {
                continue;
            }

            $rawValue = $value[$key];

            if (! is_string($rawValue)) {
                throw ValidationException::withMessages(['value' => __('Chaque champ du profil doit être une chaîne de caractères.')]);
            }

            $trimmedValue = trim($rawValue);

            if ($trimmedValue === '') {
                continue;
            }

            $result[$key] = Str::limit($trimmedValue, $maxLength, '');
        }

        return $result;
    }

    /**
     * Cartes de démarrage personnalisées du constructeur de prompts (étape 1 du wizard) - un
     * membre connecté peut en ajouter jusqu'à 10, en plus des 9 cartes système fixes (non
     * stockées ici). Chaque carte : {id, title, icon, query_template, hidden}. Entrées
     * malformées FILTRÉES (pas de rejet global), même esprit que sanitizeCustomColors/Durations
     * - une carte invalide ne doit jamais faire échouer la sauvegarde des autres.
     */
    private function sanitizeCustomCards(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages(['value' => __('Format de cartes invalide.')]);
        }

        $cards = [];
        $seenIds = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = is_string($item['title'] ?? null) ? trim($item['title']) : '';
            if ($title === '') {
                continue; // une carte sans titre n'a pas de sens - ignorée plutôt que de bloquer le reste.
            }

            $id = $item['id'] ?? null;
            if (! is_string($id) || ! preg_match('/^[a-z0-9_\-]{1,40}$/i', $id)) {
                $id = 'custom_'.Str::random(10);
            }

            // Round 93 (2026-07-27, passe adversariale) : le format de l'id était validé mais
            // jamais son unicité - un appel API direct (hors JS client, qui génère des id
            // quasi-uniques via _genCardId()) pouvait persister 2 cartes de même id, rendant la
            // 2e inatteignable via l'UI (findIndex() côté JS ne trouve toujours que la 1re).
            while (isset($seenIds[$id])) {
                $id = 'custom_'.Str::random(10);
            }
            $seenIds[$id] = true;

            $icon = is_string($item['icon'] ?? null) ? trim($item['icon']) : '';
            if ($icon === '') {
                $icon = '⭐';
            }
            $icon = Str::limit($icon, 8, '');

            $queryTemplate = is_string($item['query_template'] ?? null) ? trim($item['query_template']) : '';
            $queryTemplate = Str::limit($queryTemplate, 500, '');

            $cards[] = [
                'id' => $id,
                'title' => Str::limit($title, 60, ''),
                'icon' => $icon,
                'query_template' => $queryTemplate,
                'hidden' => (bool) ($item['hidden'] ?? false),
            ];
        }

        return array_slice($cards, 0, 10);
    }
}
