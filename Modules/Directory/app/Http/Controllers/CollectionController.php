<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Directory\Models\ToolCollection;

class CollectionController extends Controller
{
    public function index(): View
    {
        $collections = ToolCollection::where('is_public', true)
            ->withCount('tools')
            ->latest()
            ->paginate(12);

        return view('directory::public.collections.index', compact('collections'));
    }

    public function show(string $slug): View
    {
        $collection = ToolCollection::where('slug', $slug)
            ->where('is_public', true)
            ->with('tools')
            ->firstOrFail();

        return view('directory::public.collections.show', compact('collection'));
    }

    public function listJson(Request $request): JsonResponse
    {
        $toolId = (int) $request->query('tool_id', 0);

        $collections = ToolCollection::forUser((int) auth()->id())
            ->latest()
            ->get(['id', 'name', 'is_public'])
            ->map(function (ToolCollection $c) use ($toolId): array {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'is_public' => $c->is_public,
                    'has_tool' => $toolId > 0 && $c->hasTool($toolId),
                ];
            });

        return response()->json($collections);
    }

    public function myCollections(): View
    {
        $collections = ToolCollection::where('user_id', auth()->id())
            ->withCount('tools')
            ->latest()
            ->paginate(12);

        return view('directory::public.collections.my', compact('collections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        ToolCollection::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return redirect()->route('collections.my')
            ->with('success', 'Collection créée avec succès.');
    }

    public function destroy(ToolCollection $collection): RedirectResponse
    {
        if ($collection->user_id !== auth()->id()) {
            abort(403);
        }

        $collection->delete();

        return redirect()->route('collections.my')
            ->with('success', 'Collection supprimée avec succès.');
    }

    public function toggleTool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collection_id' => 'required|integer|exists:tool_collections,id',
            'tool_id' => 'required|integer|exists:directory_tools,id',
        ]);

        $collection = ToolCollection::where('id', $validated['collection_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $toolId = $validated['tool_id'];

        if ($collection->hasTool($toolId)) {
            $collection->removeTool($toolId);
            $added = false;
        } else {
            $collection->addTool($toolId);
            $added = true;
        }

        return response()->json([
            'added' => $added,
            'count' => $collection->tools()->count(),
        ]);
    }
}
