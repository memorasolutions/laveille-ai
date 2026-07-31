<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Tools\Models\SavedPrompt;

class SavedPromptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SavedPrompt::forUser(auth()->id());

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->search($search);
        }

        $tag = trim((string) $request->input('tag'));
        if ($tag !== '') {
            $query->withTag($tag);
        }

        if ($request->boolean('favorite')) {
            $query->favorite();
        }

        // Round 48 (2026-07-27) : tiebreaker sur id - created_at seul (granularité seconde) n'ordonne
        // pas de façon déterministe des lignes créées dans la même seconde (ex. importLocalCustomCards
        // en rafale via Promise.all), causant des doublons/omissions en naviguant entre pages.
        $prompts = $query->latest()->orderByDesc('id')->paginate(20);

        // Tags distincts de l'utilisateur, indépendamment des filtres/pagination actifs
        // (alimente les "chips" de filtre côté front, même quand la page courante est filtrée).
        $availableTags = SavedPrompt::forUser(auth()->id())
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            // Round 44 (2026-07-27) : filter() sans callback applique la sémantique PHP
            // array_filter par défaut, qui retire aussi la chaîne "0" (un tag littéral valide,
            // accepté par sanitizeTags()) - il devenait pleinement filtrable via ?tag=0 mais
            // disparaissait silencieusement des chips de filtre.
            ->filter(fn ($tag) => $tag !== null && $tag !== '')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $payload = array_merge($prompts->toArray(), [
            'available_tags' => $availableTags,
        ]);

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prompt_text' => 'required|string|max:20000',
            'params' => ['nullable', 'array', $this->paramsSizeRule()],
            'is_public' => 'boolean',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:30',
            'is_favorite' => 'boolean',
        ]);

        if (isset($validated['tags'])) {
            $validated['tags'] = $this->sanitizeTags($validated['tags']);
        }

        $prompt = SavedPrompt::create([
            'user_id' => auth()->id(),
            ...$validated,
        ]);

        return response()->json($prompt, 201);
    }

    public function update(Request $request, string $publicId): JsonResponse
    {
        $prompt = SavedPrompt::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'prompt_text' => 'sometimes|required|string|max:20000',
            'params' => ['nullable', 'array', $this->paramsSizeRule()],
            'is_public' => 'boolean',
            'tags' => 'sometimes|nullable|array|max:5',
            'tags.*' => 'string|max:30',
            'is_favorite' => 'sometimes|boolean',
        ]);

        if (isset($validated['tags'])) {
            $validated['tags'] = $this->sanitizeTags($validated['tags']);
        }

        $prompt->update($validated);

        return response()->json($prompt);
    }

    /**
     * Récupère UN prompt par public_id, sans passer par la liste paginée (index() est limité
     * à paginate(20)) - nécessaire pour le flux d'édition ?edit={public_id} du wizard, qui sinon
     * échoue silencieusement pour tout prompt situé au-delà de la 1re page de la bibliothèque.
     */
    public function show(string $publicId): JsonResponse
    {
        $prompt = SavedPrompt::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        return response()->json($prompt);
    }

    public function destroy(string $publicId): JsonResponse
    {
        $prompt = SavedPrompt::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();
        $prompt->delete();

        return response()->json(null, 204);
    }

    /**
     * Duplique un prompt sauvegardé de l'utilisateur connecté : copie prompt_text/params/tags,
     * jamais publique par défaut, jamais favori par défaut (évite la surprise d'une copie
     * qui apparaîtrait déjà "étoilée" ou publiée sans action explicite de l'utilisateur).
     * Scope IDOR strict : public_id + user_id, jamais l'id interne.
     */
    public function duplicate(string $publicId): JsonResponse
    {
        $original = SavedPrompt::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        $duplicate = SavedPrompt::create([
            'user_id' => auth()->id(),
            'name' => Str::limit(__('Copie de :name', ['name' => $original->name]), 255, ''),
            'prompt_text' => $original->prompt_text,
            'params' => $original->params,
            'tags' => $original->tags,
            'is_public' => false,
            'is_favorite' => false,
        ]);

        return response()->json($duplicate, 201);
    }

    /**
     * Round 34 (2026-07-27) : `params` n'a en pratique que quelques clés courtes (verb,
     * temperature, etc. - cf. usages réels dans SavedPromptControllerTest) mais n'avait aucune
     * borne de taille, contrairement à prompt_text (max:20000) et tags (max:5 x max:30) -
     * trouvé par la passe adversariale (payload de plusieurs Mo accepté sans erreur).
     */
    private function paramsSizeRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail): void {
            if (is_array($value) && strlen(json_encode($value)) > 10000) {
                $fail(__('Les paramètres du prompt sont trop volumineux.'));
            }
        };
    }

    private function sanitizeTags(?array $tags): array
    {
        $cleaned = array_filter(array_map('trim', $tags ?? []), fn ($tag) => $tag !== '');
        $deduped = array_intersect_key($cleaned, array_unique(array_map('mb_strtolower', $cleaned)));

        return array_values(array_slice($deduped, 0, 5));
    }
}
