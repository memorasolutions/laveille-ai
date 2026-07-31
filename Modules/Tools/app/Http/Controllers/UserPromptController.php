<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tools\Models\SavedPrompt;

class UserPromptController
{
    public function index(Request $request): View
    {
        $query = SavedPrompt::forUser((int) auth()->id());

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

        $availableTags = SavedPrompt::forUser((int) auth()->id())
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

        $currentFilters = [
            'search' => $search,
            'tag' => $tag,
            'favorite' => $request->boolean('favorite'),
        ];

        $promptProfile = auth()->user()->tool_preferences['constructeur-prompts']['prompt_profile'] ?? [];

        return view('tools::user.prompts.index', compact('prompts', 'availableTags', 'currentFilters', 'promptProfile'));
    }
}
