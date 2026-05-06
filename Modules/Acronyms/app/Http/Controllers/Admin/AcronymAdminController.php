<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Acronyms\Models\Acronym;
use Modules\Acronyms\Models\AcronymCategory;
use Modules\Core\Traits\ParsesWsdAliases;
use Modules\Settings\Facades\Settings;

class AcronymAdminController extends Controller
{
    use ParsesWsdAliases;

    public function index(): View
    {
        $acronyms = Acronym::with('category')->orderBy('acronym->'.app()->getLocale())->paginate((int) Settings::get('acronyms.admin_per_page', 20));

        return view('acronyms::admin.index', compact('acronyms'));
    }

    public function create(): View
    {
        $categories = AcronymCategory::orderBy('sort_order')->get();

        return view('acronyms::admin.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'acronym' => 'required|string|max:50',
            'full_name' => 'required|string|max:500',
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:500',
            'logo_url' => 'nullable|url|max:500',
            'domain' => 'nullable|string|max:50',
            'acronym_category_id' => 'nullable|exists:acronym_categories,id',
            'is_published' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            // 2026-05-06 #158 : auto-link WSD
            'match_strategy' => 'nullable|in:loose,partial_case_sensitive,case_sensitive,exact_phrase,never_auto',
            'aliases' => 'nullable|string',
        ]);

        $slug = Str::slug($validated['acronym']);
        $locale = app()->getLocale();

        Acronym::create([
            'acronym' => [$locale => $validated['acronym'], 'fr' => $validated['acronym']],
            'full_name' => [$locale => $validated['full_name'], 'fr' => $validated['full_name']],
            'slug' => [$locale => $slug, 'fr' => $slug],
            'description' => $validated['description'] ? [$locale => $validated['description'], 'fr' => $validated['description']] : null,
            'website_url' => $validated['website_url'] ?? null,
            'logo_url' => $validated['logo_url'] ?? null,
            'domain' => $validated['domain'] ?? 'education',
            'acronym_category_id' => $validated['acronym_category_id'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'match_strategy' => $validated['match_strategy'] ?? 'case_sensitive',
            'aliases' => self::parseAliases($validated['aliases'] ?? null),
        ]);

        return redirect()->route('admin.acronyms.index')->with('success', __('Acronyme créé.'));
    }

    public function edit(Acronym $acronym): View
    {
        $categories = AcronymCategory::orderBy('sort_order')->get();

        return view('acronyms::admin.edit', compact('acronym', 'categories'));
    }

    public function update(Request $request, Acronym $acronym): RedirectResponse
    {
        $validated = $request->validate([
            'acronym' => 'required|string|max:50',
            'full_name' => 'required|string|max:500',
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:500',
            'logo_url' => 'nullable|url|max:500',
            'domain' => 'nullable|string|max:50',
            'acronym_category_id' => 'nullable|exists:acronym_categories,id',
            'is_published' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            // 2026-05-06 #158 : auto-link WSD
            'match_strategy' => 'nullable|in:loose,partial_case_sensitive,case_sensitive,exact_phrase,never_auto',
            'aliases' => 'nullable|string',
        ]);

        $locale = app()->getLocale();
        $slug = Str::slug($validated['acronym']);

        $acronym->setTranslation('acronym', $locale, $validated['acronym']);
        $acronym->setTranslation('acronym', 'fr', $validated['acronym']);
        $acronym->setTranslation('full_name', $locale, $validated['full_name']);
        $acronym->setTranslation('full_name', 'fr', $validated['full_name']);
        $acronym->setTranslation('slug', $locale, $slug);
        $acronym->setTranslation('slug', 'fr', $slug);

        if ($validated['description']) {
            $acronym->setTranslation('description', $locale, $validated['description']);
            $acronym->setTranslation('description', 'fr', $validated['description']);
        }

        $acronym->website_url = $validated['website_url'] ?? null;
        $acronym->logo_url = $validated['logo_url'] ?? null;
        $acronym->domain = $validated['domain'] ?? 'education';
        $acronym->acronym_category_id = $validated['acronym_category_id'] ?? null;
        $acronym->is_published = $request->boolean('is_published');
        $acronym->sort_order = $validated['sort_order'] ?? 0;
        $acronym->match_strategy = $validated['match_strategy'] ?? 'case_sensitive';
        $acronym->aliases = self::parseAliases($validated['aliases'] ?? null);
        $acronym->save();

        return redirect()->route('admin.acronyms.index')->with('success', __('Acronyme mis à jour.'));
    }

    public function destroy(Acronym $acronym): RedirectResponse
    {
        $acronym->delete();

        return redirect()->route('admin.acronyms.index')->with('success', __('Acronyme supprimé.'));
    }

    /**
     * Autosave draft for an acronym.
     */
    public function autosave(\Illuminate\Http\Request $request, \Modules\Acronyms\Models\Acronym $acronym): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()?->can('update', $acronym) ?? true, 403);

        $validated = $request->validate([
            'acronym' => 'nullable|string',
            'full_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $acronym->fill(array_filter($validated, fn ($v) => $v !== null));

        if ($acronym->isDirty()) {
            $acronym->saveQuietly();

            return response()->json([
                'success' => true,
                'saved_at' => now()->toDateTimeString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'saved_at' => null,
        ]);
    }

    // 2026-05-06 #158 : parseAliases() mutualisé via trait Core/ParsesWsdAliases (DRY).
}
