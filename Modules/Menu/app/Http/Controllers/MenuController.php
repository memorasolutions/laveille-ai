<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Menu\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Services\MenuService;

class MenuController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService,
    ) {}

    public function index(): View
    {
        $menus = Menu::withCount('allItems')->get();
        $locations = $this->menuService->getAvailableLocations();

        return view('menu::admin.index', compact('menus', 'locations'));
    }

    public function create(): View
    {
        $locations = $this->menuService->getAvailableLocations();

        return view('menu::admin.create', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name',
            'location' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $menu = Menu::create($validated);
        $this->menuService->clearCache();

        return redirect()->route('admin.menus.edit', $menu)
            ->with('success', 'Menu créé avec succès.');
    }

    public function edit(Menu $menu): View
    {
        $menu->load(['allItems' => fn ($q) => $q->orderBy('order')]);
        $locations = $this->menuService->getAvailableLocations();

        $linkableOptions = $this->getLinkableOptions();

        return view('menu::admin.edit', compact('menu', 'locations', 'linkableOptions'));
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name,'.$menu->id,
            'location' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $menu->update($validated);
        $this->menuService->clearCache();

        return redirect()->back()->with('success', 'Menu mis à jour avec succès.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        $this->menuService->clearCache();

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu supprimé avec succès.');
    }

    public function saveItems(Request $request, Menu $menu): JsonResponse
    {
        $items = $request->validate([
            'items' => 'present|array',
            'items.*.id' => 'nullable|integer',
            'items.*.title' => 'required|string|max:255',
            'items.*.type' => 'required|in:custom,page,route,category',
            'items.*.url' => 'nullable|string|max:2048',
            'items.*.route_name' => 'nullable|string|max:255',
            'items.*.linkable_type' => 'nullable|string',
            'items.*.linkable_id' => 'nullable|integer',
            'items.*.target' => 'string|in:_self,_blank',
            'items.*.icon' => 'nullable|string|max:100',
            'items.*.css_classes' => 'nullable|string|max:255',
            'items.*.parent_id' => 'nullable|integer',
            'items.*.order' => 'integer|min:0',
            'items.*.enabled' => 'boolean',
        ]);

        $existingIds = $menu->allItems()->pluck('id')->toArray();
        $submittedIds = [];

        foreach ($items['items'] as $data) {
            $data['menu_id'] = $menu->id;

            if (! empty($data['id'])) {
                MenuItem::where('id', $data['id'])->where('menu_id', $menu->id)->update($data);
                $submittedIds[] = $data['id'];
            } else {
                unset($data['id']);
                $item = MenuItem::create($data);
                $submittedIds[] = $item->id;
            }
        }

        $toDelete = array_diff($existingIds, $submittedIds);
        if (! empty($toDelete)) {
            MenuItem::whereIn('id', $toDelete)->where('menu_id', $menu->id)->delete();
        }

        $this->menuService->clearCache();

        return response()->json(['success' => true, 'message' => 'Éléments du menu enregistrés.']);
    }

    private function getLinkableOptions(): array
    {
        $options = [];

        if (class_exists(\Modules\Pages\Models\StaticPage::class)) {
            $options['pages'] = \Modules\Pages\Models\StaticPage::where('status', 'published')
                ->get(['id', 'title', 'slug']);
        }

        if (class_exists(\Modules\Blog\Models\Category::class)) {
            $options['categories'] = \Modules\Blog\Models\Category::all(['id', 'name', 'slug']);
        }

        return $options;
    }
}
