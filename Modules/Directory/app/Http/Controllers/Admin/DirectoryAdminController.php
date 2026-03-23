<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

class DirectoryAdminController extends Controller
{
    public function index(): View
    {
        $tools = Tool::with('categories')->orderBy('sort_order')->paginate(20);

        return view('directory::admin.index', compact('tools'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('sort_order')->get();

        return view('directory::admin.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'url' => 'nullable|url',
            'pricing' => 'required|in:free,freemium,paid,open_source,enterprise',
            'categories' => 'nullable|array',
            'logo' => 'nullable|image|max:2048',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $locale = app()->getLocale();
        $slug = Str::slug($validated['name']);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('directory/logos', 'public');
        }

        $tool = Tool::create([
            'name' => [$locale => $validated['name'], 'fr' => $validated['name']],
            'slug' => [$locale => $slug, 'fr' => $slug],
            'description' => [$locale => $validated['description'] ?? '', 'fr' => $validated['description'] ?? ''],
            'short_description' => [$locale => $validated['short_description'] ?? '', 'fr' => $validated['short_description'] ?? ''],
            'url' => $validated['url'],
            'pricing' => $validated['pricing'],
            'logo' => $logoPath ? 'storage/' . $logoPath : null,
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (! empty($validated['categories'])) {
            $tool->categories()->sync($validated['categories']);
        }

        return redirect()->route('admin.directory.index')->with('success', __('Outil ajouté à l\'annuaire.'));
    }

    public function edit(Tool $tool): View
    {
        $categories = Category::orderBy('sort_order')->get();

        return view('directory::admin.edit', compact('tool', 'categories'));
    }

    public function update(Request $request, Tool $tool): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'url' => 'nullable|url',
            'pricing' => 'required|in:free,freemium,paid,open_source,enterprise',
            'categories' => 'nullable|array',
            'logo' => 'nullable|image|max:2048',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $locale = app()->getLocale();
        $tool->setTranslation('name', $locale, $validated['name']);
        $tool->setTranslation('name', 'fr', $validated['name']);
        $tool->setTranslation('description', $locale, $validated['description'] ?? '');
        $tool->setTranslation('short_description', $locale, $validated['short_description'] ?? '');
        $tool->url = $validated['url'];
        $tool->pricing = $validated['pricing'];
        $tool->is_featured = $request->boolean('is_featured');
        $tool->sort_order = $validated['sort_order'] ?? 0;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('directory/logos', 'public');
            $tool->logo = 'storage/' . $logoPath;
        }

        $tool->save();

        $tool->categories()->sync($validated['categories'] ?? []);

        return redirect()->route('admin.directory.index')->with('success', __('Outil mis à jour.'));
    }

    public function destroy(Tool $tool): RedirectResponse
    {
        $tool->delete();

        return redirect()->route('admin.directory.index')->with('success', __('Outil supprimé.'));
    }
}
