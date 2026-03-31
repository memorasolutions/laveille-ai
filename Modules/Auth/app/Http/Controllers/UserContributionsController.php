<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class UserContributionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();

        $items = collect();

        if (class_exists(\Modules\Directory\Models\ToolSuggestion::class)) {
            $items = $items->merge(
                \Modules\Directory\Models\ToolSuggestion::where('user_id', $user->id)->with('suggestable')->latest()->get()
                    ->map(fn ($s) => (object) [
                        'type' => 'suggestion', 'icon' => '💡', 'color' => '#f59e0b',
                        'label' => __('Suggestion'), 'name' => $s->getItemName(),
                        'preview' => (\Modules\Directory\Models\ToolSuggestion::fieldLabels()[$s->field] ?? $s->field) . ' — ' . \Str::limit($s->suggested_value, 60),
                        'status' => $s->status, 'link' => $this->suggestionLink($s),
                        'created_at' => $s->created_at, 'raw' => $s,
                    ])
            );
        }

        if (class_exists(\Modules\Roadmap\Models\Vote::class)) {
            $items = $items->merge(
                \Modules\Roadmap\Models\Vote::where('user_id', $user->id)->with('idea')->latest()->get()
                    ->filter(fn ($v) => $v->idea)
                    ->map(fn ($v) => (object) [
                        'type' => 'vote', 'icon' => '👍', 'color' => '#0B7285',
                        'label' => __('Vote roadmap'), 'name' => $v->idea->title,
                        'preview' => $v->idea->status->label(),
                        'status' => null, 'status_badge' => $v->idea->status->color(),
                        'status_label' => $v->idea->status->label(),
                        'link' => null, 'created_at' => $v->created_at, 'raw' => $v,
                    ])
            );
        }

        if (class_exists(\Modules\Directory\Models\ToolResource::class)) {
            $items = $items->merge(
                \Modules\Directory\Models\ToolResource::where('user_id', $user->id)->with('tool')->latest()->get()
                    ->map(fn ($r) => (object) [
                        'type' => 'resource', 'icon' => '📚', 'color' => '#0891B2',
                        'label' => __('Ressource'), 'name' => \Str::limit($r->title, 50),
                        'preview' => ($r->tool->name ?? '—') . ' — ' . $r->type,
                        'status' => $r->is_approved ? 'approved' : 'pending',
                        'link' => ($r->tool && \Route::has('directory.show')) ? route('directory.show', $r->tool->slug) . '#resources' : null,
                        'created_at' => $r->created_at, 'raw' => $r,
                    ])
            );
        }

        $items = $items->sortByDesc('created_at')->values();
        $types = $items->pluck('type')->unique()->values();

        return view('auth::contributions.index', compact('user', 'items', 'types'));
    }

    private function suggestionLink($suggestion): ?string
    {
        if (! $suggestion->suggestable) return null;
        $type = class_basename($suggestion->suggestable_type);
        $slug = $suggestion->suggestable->slug ?? '';
        if ($type === 'Tool' && \Route::has('directory.show')) return route('directory.show', $slug);
        if ($type === 'Term' && \Route::has('dictionary.show')) return route('dictionary.show', $slug);
        if ($type === 'Acronym' && \Route::has('acronyms.show')) return route('acronyms.show', $slug);
        return null;
    }
}
