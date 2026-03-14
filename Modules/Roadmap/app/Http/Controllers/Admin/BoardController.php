<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Roadmap\Models\Board;

class BoardController extends Controller
{
    public function index()
    {
        $boards = Board::ordered()->withCount('ideas')->paginate(20);

        return view('roadmap::admin.boards.index', compact('boards'));
    }

    public function create()
    {
        return view('roadmap::admin.boards.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Board::create($validated);

        return redirect()->route('admin.roadmap.boards.index')
            ->with('success', __('Board created successfully.'));
    }

    public function edit(Board $board)
    {
        return view('roadmap::admin.boards.edit', compact('board'));
    }

    public function update(Request $request, Board $board)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'color' => 'nullable|string|max:7',
        ]);

        if ($board->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $board->update($validated);

        return redirect()->back()->with('success', __('Board updated successfully.'));
    }

    public function destroy(Board $board)
    {
        $board->delete();

        return redirect()->back()->with('success', __('Board deleted successfully.'));
    }
}
