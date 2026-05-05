<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class UserSavedController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();

        // Collecter les sauvegardes de chaque outil avec un type identifiant
        $items = collect();

        if (class_exists(\Modules\Tools\Models\SavedPrompt::class)) {
            $items = $items->merge(
                \Modules\Tools\Models\SavedPrompt::forUser($user->id)->latest()->get()
                    ->map(fn ($p) => (object) ['id' => $p->id, 'public_id' => $p->public_id, 'type' => 'prompt', 'name' => $p->name, 'preview' => \Str::limit($p->prompt_text, 80), 'tool_name' => __('Constructeur de prompts'), 'tool_slug' => 'constructeur-prompts', 'tool_icon' => '✨', 'tool_color' => '#8B5CF6', 'api_path' => '/api/prompts/', 'created_at' => $p->created_at])
            );
        }

        if (class_exists(\Modules\Tools\Models\SavedTeamPreset::class)) {
            $items = $items->merge(
                \Modules\Tools\Models\SavedTeamPreset::forUser($user->id)->latest()->get()
                    ->map(fn ($p) => (object) ['id' => $p->id, 'public_id' => $p->public_id, 'type' => 'team', 'name' => $p->name, 'preview' => \Str::limit($p->config_text, 80), 'tool_name' => __('Générateur d\'équipes'), 'tool_slug' => 'generateur-equipes', 'tool_icon' => '👥', 'tool_color' => '#0B7285', 'api_path' => '/api/team-presets/', 'created_at' => $p->created_at])
            );
        }

        if (class_exists(\Modules\Tools\Models\SavedDrawPreset::class)) {
            $items = $items->merge(
                \Modules\Tools\Models\SavedDrawPreset::forUser($user->id)->latest()->get()
                    ->map(fn ($p) => (object) ['id' => $p->id, 'public_id' => $p->public_id, 'type' => 'draw', 'name' => $p->name, 'preview' => \Str::limit($p->config_text, 80), 'tool_name' => __('Tirage de présentations'), 'tool_slug' => 'tirage-presentations', 'tool_icon' => '🎲', 'tool_color' => '#E67E22', 'api_path' => '/api/draw-presets/', 'created_at' => $p->created_at])
            );
        }

        if (class_exists(\Modules\Tools\Models\SavedWheelPreset::class)) {
            $items = $items->merge(
                \Modules\Tools\Models\SavedWheelPreset::forUser($user->id)->latest()->get()
                    ->map(fn ($p) => (object) ['id' => $p->id, 'public_id' => $p->public_id, 'type' => 'wheel', 'name' => $p->name, 'preview' => \Str::limit($p->config_text, 80), 'tool_name' => __('Roue de tirage'), 'tool_slug' => 'roue-tirage', 'tool_icon' => '🎡', 'tool_color' => '#ef4444', 'api_path' => '/api/wheel-presets/', 'created_at' => $p->created_at])
            );
        }

        if (class_exists(\Modules\Tools\Models\SavedQrPreset::class)) {
            $items = $items->merge(
                \Modules\Tools\Models\SavedQrPreset::forUser($user->id)->latest()->get()
                    ->map(fn ($p) => (object) ['id' => $p->id, 'public_id' => $p->public_id, 'type' => 'qr', 'name' => $p->name, 'preview' => \Str::limit($p->config_text, 80), 'tool_name' => __('Générateur de code QR'), 'tool_slug' => 'code-qr', 'tool_icon' => '📱', 'tool_color' => '#6366f1', 'api_path' => '/api/qr-presets/', 'created_at' => $p->created_at])
            );
        }

        // 2026-05-05 #117 : grilles mots-croisés sauvegardées (DRY pattern Memora)
        if (class_exists(\Modules\Tools\Models\SavedCrosswordPreset::class)) {
            $items = $items->merge(
                \Modules\Tools\Models\SavedCrosswordPreset::forUser($user->id)->latest()->get()
                    ->map(function ($p) {
                        $lines = preg_split('/\r\n|\n|\r/', trim((string) $p->config_text));
                        $pairsCount = collect($lines)->filter(fn ($l) => str_contains($l, ' / '))->count();
                        $diff = (string) ($p->params['difficulty'] ?? 'Moyen');
                        $public = $p->is_public ? __('publique') : __('privée');
                        return (object) [
                            'id' => $p->id,
                            'public_id' => $p->public_id,
                            'type' => 'crossword',
                            'name' => $p->name,
                            'preview' => $pairsCount.' '.__('mots').' · '.$diff.' · '.$public,
                            'tool_name' => __('Mots croisés'),
                            'tool_slug' => 'mots-croises',
                            'tool_icon' => '🧩',
                            'tool_color' => '#0B7285',
                            'api_path' => '/api/crossword-presets/',
                            'created_at' => $p->created_at,
                        ];
                    })
            );
        }

        $items = $items->sortByDesc('created_at')->values();

        // Types disponibles pour les chips filtres
        $types = $items->pluck('type')->unique()->values();

        return view('auth::saved.index', compact('user', 'items', 'types'));
    }
}
