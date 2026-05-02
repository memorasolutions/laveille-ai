<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Tools\Models\SavedCrosswordPreset;

class UserCrosswordController
{
    public function index(): View
    {
        $presets = SavedCrosswordPreset::forUser((int) auth()->id())
            ->orderByDesc('updated_at')
            ->paginate(20);

        // Compteur de paires depuis config_text (1 ligne = 1 paire)
        $presets->getCollection()->transform(function (SavedCrosswordPreset $p) {
            $lines = preg_split('/\r\n|\n|\r/', trim((string) $p->config_text));
            $p->pairs_count = collect($lines)->filter(fn ($l) => str_contains($l, ' / '))->count();

            return $p;
        });

        return view('tools::user.crosswords.index', compact('presets'));
    }

    public function edit(string $publicId): RedirectResponse
    {
        $preset = SavedCrosswordPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        return redirect('/outils/mots-croises?preset='.$preset->public_id);
    }
}
