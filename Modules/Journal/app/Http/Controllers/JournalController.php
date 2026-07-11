<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 */

declare(strict_types=1);

namespace Modules\Journal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Journal\Livewire\JournalBuilder;
use Modules\Journal\Models\Journal;
use Modules\Journal\Services\JournalBlockService;

class JournalController extends Controller
{
    /**
     * « Mes journaux » : liste privée des journaux de l'utilisateur connecté
     * (brouillons et publiés).
     */
    public function index(Request $request): View
    {
        $journals = Journal::where('user_id', $request->user()->id)
            ->withCount('blocks')
            ->latest('updated_at')
            ->get();

        return view('journal::index', ['journals' => $journals]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('journal::create', ['templates' => JournalBuilder::TEMPLATES]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'template' => ['required', Rule::in(array_keys(JournalBuilder::TEMPLATES))],
        ]);

        $baseSlug = Str::slug($validated['title']) ?: 'journal';
        $slug = $baseSlug;
        $suffix = 1;

        while (Journal::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.(++$suffix);
        }

        $journal = Journal::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'journal_date' => now()->toDateString(),
            'template' => $validated['template'],
            'is_published' => false,
        ]);

        return redirect()->route('journal.edit', $journal);
    }

    /**
     * Affichage public de lecture d'un journal (visibilité gérée par
     * JournalPolicy::view : publié = tout le monde, brouillon = propriétaire).
     */
    public function show(Journal $journal): View
    {
        Gate::authorize('view', $journal);

        return view('journal::show', [
            'journal' => $journal,
            'blocks' => $journal->blocks()->get(),
            'isOwner' => auth()->check() && auth()->id() === $journal->user_id,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Journal $journal): View
    {
        Gate::authorize('update', $journal);

        return view('journal::edit', ['journal' => $journal]);
    }

    /**
     * Ajoute rapidement un bloc « source » (actualité/terme/outil) à l'un des
     * journaux de l'utilisateur connecté, depuis le bouton « + Ajouter à mon
     * journal » affiché sur les pages publiques concernées.
     */
    public function quickAdd(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'journal_id' => ['required', 'integer'],
            'source_type' => ['required', 'string', Rule::in(array_keys(JournalBlockService::SOURCE_MAP))],
            'source_id' => ['required', 'integer'],
        ]);

        $journal = Journal::findOrFail($validated['journal_id']);
        Gate::authorize('update', $journal);

        try {
            app(JournalBlockService::class)->addFromSource(
                $journal,
                $validated['source_type'],
                $validated['source_id']
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ajout impossible.'], 422);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Journal $journal): RedirectResponse
    {
        Gate::authorize('delete', $journal);

        $journal->delete();

        return redirect()->route('journal.index')->with('status', 'Journal supprimé.');
    }
}
